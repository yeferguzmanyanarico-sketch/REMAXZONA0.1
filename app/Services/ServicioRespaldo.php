<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class ServicioRespaldo
{
    /**
     * Ejecuta un respaldo completo (base de datos + archivos multimedia) en storage privado.
     *
     * @return array{directorio: string, archivos: array<string, array{tamano: int, sha256: string}>, advertencias: array<int, string>}
     */
    public function ejecutarRespaldo(): array
    {
        $entorno = app()->environment();
        $marca = now('America/Lima')->format('Y-m-d_His');
        $directorioBase = storage_path('app/private/respaldos/' . $entorno);
        $directorioDestino = $directorioBase . DIRECTORY_SEPARATOR . $marca;

        // Nunca sobrescribir respaldos existentes
        if (is_dir($directorioDestino)) {
            $directorioDestino .= '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        }

        if (! is_dir($directorioDestino) && ! mkdir($directorioDestino, 0750, true)) {
            throw new RuntimeException('No se pudo crear el directorio de respaldo.');
        }

        $archivosGenerados = [];
        $advertencias = [];

        Log::channel('respaldos')->info('Inicio de respaldo del sistema.', [
            'entorno' => $entorno,
            'destino' => $directorioDestino,
        ]);

        // 1. Respaldo de base de datos
        $resultadoBd = $this->respaldarBaseDatos($directorioDestino);
        if ($resultadoBd['archivo'] !== null) {
            $archivosGenerados[] = $resultadoBd['archivo'];
        }
        if ($resultadoBd['advertencia'] !== null) {
            $advertencias[] = $resultadoBd['advertencia'];
        }

        // 2. Respaldo de fotografías de propiedades, vendedores y multimedia
        $rutaZipMultimedia = $directorioDestino . DIRECTORY_SEPARATOR . 'multimedia.zip';
        $this->respaldarMultimedia($rutaZipMultimedia);
        $archivosGenerados[] = $rutaZipMultimedia;

        // 3. Manifiesto con verificación de integridad SHA-256
        $hashes = [];
        foreach ($archivosGenerados as $rutaArchivo) {
            $nombre = basename($rutaArchivo);
            $hashes[$nombre] = [
                'tamano' => (int) filesize($rutaArchivo),
                'sha256' => (string) hash_file('sha256', $rutaArchivo),
            ];
        }

        $manifiesto = [
            'aplicacion' => config('app.name'),
            'entorno' => $entorno,
            'fecha' => now('America/Lima')->toIso8601String(),
            'conexion_bd' => config('database.default'),
            'archivos' => $hashes,
            'advertencias' => $advertencias,
        ];

        $rutaManifiesto = $directorioDestino . DIRECTORY_SEPARATOR . 'manifiesto.json';
        file_put_contents(
            $rutaManifiesto,
            json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        Log::channel('respaldos')->info('Respaldo completado correctamente.', [
            'directorio' => $directorioDestino,
            'archivos' => array_keys($hashes),
            'advertencias' => $advertencias,
        ]);

        return [
            'directorio' => $directorioDestino,
            'archivos' => $hashes,
            'advertencias' => $advertencias,
        ];
    }

    /**
     * Verifica la integridad SHA-256 de un respaldo existente sin modificar datos.
     *
     * @return array{valido: bool, detalles: array<string, string>}
     */
    public function verificarIntegridad(string $directorioRespaldo): array
    {
        $rutaManifiesto = rtrim($directorioRespaldo, '/\\') . DIRECTORY_SEPARATOR . 'manifiesto.json';

        if (! is_file($rutaManifiesto)) {
            return [
                'valido' => false,
                'detalles' => ['manifiesto' => 'No existe manifiesto.json en el directorio indicado.'],
            ];
        }

        $manifiesto = json_decode((string) file_get_contents($rutaManifiesto), true);
        if (! is_array($manifiesto) || empty($manifiesto['archivos']) || ! is_array($manifiesto['archivos'])) {
            return [
                'valido' => false,
                'detalles' => ['manifiesto' => 'El manifiesto tiene un formato inválido.'],
            ];
        }

        $valido = true;
        $detalles = [];

        foreach ($manifiesto['archivos'] as $nombre => $meta) {
            $rutaArchivo = rtrim($directorioRespaldo, '/\\') . DIRECTORY_SEPARATOR . $nombre;
            if (! is_file($rutaArchivo)) {
                $valido = false;
                $detalles[$nombre] = 'Falta el archivo.';
                continue;
            }

            $hashActual = hash_file('sha256', $rutaArchivo);
            if (! hash_equals((string) ($meta['sha256'] ?? ''), (string) $hashActual)) {
                $valido = false;
                $detalles[$nombre] = 'Hash SHA-256 no coincide.';
                continue;
            }

            $detalles[$nombre] = 'Integridad verificada (SHA-256 OK).';
        }

        return [
            'valido' => $valido,
            'detalles' => $detalles,
        ];
    }

    /**
     * @return array{archivo: ?string, advertencia: ?string}
     */
    private function respaldarBaseDatos(string $directorioDestino): array
    {
        $conexion = config('database.default');
        $config = config('database.connections.' . $conexion, []);
        $driver = $config['driver'] ?? '';

        if ($driver === 'sqlite') {
            $rutaSqlite = $config['database'] ?? '';
            if ($rutaSqlite !== ':memory:' && is_file($rutaSqlite)) {
                $destinoSqlite = $directorioDestino . DIRECTORY_SEPARATOR . 'base_datos.sqlite';
                copy($rutaSqlite, $destinoSqlite);

                return ['archivo' => $destinoSqlite, 'advertencia' => null];
            }

            return [
                'archivo' => null,
                'advertencia' => 'Base de datos SQLite en memoria; no se generó archivo de volcado.',
            ];
        }

        if ($driver !== 'mysql') {
            return [
                'archivo' => null,
                'advertencia' => "Driver de base de datos '{$driver}' no soportado para volcado automático.",
            ];
        }

        $rutaSql = $directorioDestino . DIRECTORY_SEPARATOR . 'base_datos.sql';
        $binario = env('MYSQLDUMP_PATH', 'mysqldump');

        $argumentos = [
            $binario,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--host=' . (string) ($config['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($config['port'] ?? '3306'),
            '--user=' . (string) ($config['username'] ?? ''),
            '--result-file=' . $rutaSql,
            (string) ($config['database'] ?? ''),
        ];

        $variablesEntorno = [];
        if (! empty($config['password'])) {
            $variablesEntorno['MYSQL_PWD'] = (string) $config['password'];
        }

        $resultado = Process::timeout(120)
            ->env($variablesEntorno)
            ->run($argumentos);

        if (! $resultado->successful() || ! is_file($rutaSql) || filesize($rutaSql) === 0) {
            if (is_file($rutaSql)) {
                @unlink($rutaSql);
            }

            $mensaje = 'No se pudo ejecutar mysqldump; verifica que el binario esté disponible en el servidor.';
            Log::channel('respaldos')->warning($mensaje, [
                'codigo_salida' => $resultado->exitCode(),
            ]);

            return ['archivo' => null, 'advertencia' => $mensaje];
        }

        return ['archivo' => $rutaSql, 'advertencia' => null];
    }

    private function respaldarMultimedia(string $rutaZip): void
    {
        $zip = new ZipArchive();
        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP de multimedia.');
        }

        $origenes = [
            'storage_public' => storage_path('app/public'),
            'public_images' => public_path('images'),
        ];

        $archivosAgregados = 0;

        foreach ($origenes as $prefijo => $rutaOrigen) {
            if (! is_dir($rutaOrigen)) {
                continue;
            }

            $iterador = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rutaOrigen, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterador as $archivo) {
                if (! $archivo->isFile() || $archivo->getFilename() === '.gitignore') {
                    continue;
                }

                $rutaCompleta = $archivo->getRealPath();
                if ($rutaCompleta === false) {
                    continue;
                }

                $rutaRelativa = ltrim(str_replace($rutaOrigen, '', $rutaCompleta), '/\\');
                $rutaEnZip = $prefijo . '/' . str_replace('\\', '/', $rutaRelativa);
                $zip->addFile($rutaCompleta, $rutaEnZip);
                $archivosAgregados++;
            }
        }

        if ($archivosAgregados === 0) {
            $zip->addFromString('README.txt', 'Sin archivos multimedia en el momento del respaldo.');
        }

        $zip->close();
    }
}
