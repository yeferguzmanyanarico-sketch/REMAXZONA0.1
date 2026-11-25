<?php

namespace App\Console\Commands;

use App\Services\ServicioRespaldo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RespaldarSistema extends Command
{
    protected $signature = 'sistema:respaldar';

    protected $description = 'Genera un respaldo cifrable en almacenamiento privado de BD y multimedia sin sobrescribir copias previas.';

    public function handle(ServicioRespaldo $servicioRespaldo): int
    {
        try {
            $resultado = $servicioRespaldo->ejecutarRespaldo();

            $this->info('Respaldo generado en: ' . $resultado['directorio']);
            foreach ($resultado['archivos'] as $archivo => $meta) {
                $this->line(" - {$archivo} ({$meta['tamano']} bytes, sha256: {$meta['sha256']})");
            }
            foreach ($resultado['advertencias'] as $advertencia) {
                $this->warn('Advertencia: ' . $advertencia);
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::channel('respaldos')->error('Error al generar respaldo del sistema.', [
                'excepcion' => $e::class,
                'mensaje' => $e->getMessage(),
            ]);
            $this->error('No se pudo completar el respaldo: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
