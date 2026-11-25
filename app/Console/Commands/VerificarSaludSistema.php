<?php

namespace App\Console\Commands;

use App\Services\ServicioDisponibilidad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarSaludSistema extends Command
{
    protected $signature = 'sistema:verificar-salud';

    protected $description = 'Verifica la disponibilidad de la aplicación, base de datos y almacenamiento.';

    public function handle(ServicioDisponibilidad $servicioDisponibilidad): int
    {
        $resultado = $servicioDisponibilidad->verificarEstado();
        $cuerpo = $resultado['cuerpo'];

        if ($resultado['codigo_http'] !== 200) {
            Log::channel('disponibilidad')->warning('Verificación programada detectó estado degradado.', $cuerpo);
            $this->error('Sistema en estado degradado.');

            return self::FAILURE;
        }

        $this->info('Sistema operativo (BD y almacenamiento OK).');

        return self::SUCCESS;
    }
}
