<?php

namespace App\Console\Commands;

use App\Services\ServicioRespaldo;
use Illuminate\Console\Command;

class VerificarRespaldo extends Command
{
    protected $signature = 'sistema:verificar-respaldo {directorio : Ruta absoluta del directorio de respaldo}';

    protected $description = 'Verifica la integridad SHA-256 de un respaldo sin alterar archivos ni base de datos.';

    public function handle(ServicioRespaldo $servicioRespaldo): int
    {
        $resultado = $servicioRespaldo->verificarIntegridad((string) $this->argument('directorio'));

        foreach ($resultado['detalles'] as $archivo => $detalle) {
            $this->line(" - {$archivo}: {$detalle}");
        }

        if (! $resultado['valido']) {
            $this->error('La verificación de integridad falló.');

            return self::FAILURE;
        }

        $this->info('Respaldo íntegro y verificado.');

        return self::SUCCESS;
    }
}
