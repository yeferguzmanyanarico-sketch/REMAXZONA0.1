<?php

namespace App\Console\Commands;

use App\Services\ServicioOptimizacionImagen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizarImagenes extends Command
{
    protected $signature = 'sistema:optimizar-imagenes';

    protected $description = 'Genera versiones WebP optimizadas de las imágenes de propiedades, vendedores, oficinas, portada y recursos del sitio sin eliminar originales.';

    public function handle(ServicioOptimizacionImagen $servicio): int
    {
        $convertidas = 0;

        $directoriosStorage = [
            'propiedades' => 1200,
            'vendedores' => 600,
            'vendedores/actual' => 600,
            'agentes' => 600,
            'oficinas' => 1000,
            'portadas' => 1920,
        ];

        foreach ($directoriosStorage as $directorio => $anchoMaximo) {
            if (! Storage::disk('public')->exists($directorio)) {
                continue;
            }

            foreach (Storage::disk('public')->files($directorio) as $archivo) {
                if (preg_match('/\.(jpg|jpeg|png)$/i', $archivo) !== 1) {
                    continue;
                }

                if ($servicio->generarWebpDesdeDiscoPublico($archivo, $anchoMaximo) !== null) {
                    $convertidas++;
                }
            }
        }

        $rutaImagenesPublicas = public_path('images');
        if (is_dir($rutaImagenesPublicas)) {
            foreach (scandir($rutaImagenesPublicas) ?: [] as $archivo) {
                if (preg_match('/\.(jpg|jpeg|png)$/i', $archivo) !== 1) {
                    continue;
                }

                if ($servicio->generarWebpDesdeDiscoPublico('images/' . $archivo, 1400) !== null) {
                    $convertidas++;
                }
            }
        }

        $this->info("Optimización completada. Imágenes WebP disponibles/actualizadas: {$convertidas}.");

        return self::SUCCESS;
    }
}
