<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

class ServicioOptimizacionImagen
{
    private const ANCHO_MAXIMO_DEFECTO = 1200;

    private const CALIDAD_WEBP = 82;

    /**
     * Genera una versión WebP redimensionada proporcionalmente sin eliminar la imagen original.
     * Evita conversiones repetidas si el archivo WebP ya existe y está actualizado.
     */
    public function generarWebpDesdeDiscoPublico(string $rutaRelativa, int $anchoMaximo = self::ANCHO_MAXIMO_DEFECTO): ?string
    {
        if (
            $rutaRelativa === ''
            || str_starts_with($rutaRelativa, 'http://')
            || str_starts_with($rutaRelativa, 'https://')
            || str_ends_with(strtolower($rutaRelativa), '.webp')
        ) {
            return null;
        }

        $rutaOrigen = str_starts_with($rutaRelativa, 'images/')
            ? public_path($rutaRelativa)
            : Storage::disk('public')->path($rutaRelativa);

        if (! is_file($rutaOrigen)) {
            return null;
        }

        $rutaWebp = $this->obtenerRutaWebp($rutaOrigen);
        if ($rutaWebp === null) {
            return null;
        }

        if (is_file($rutaWebp) && filemtime($rutaWebp) >= filemtime($rutaOrigen)) {
            return $this->obtenerUrlWebp($rutaRelativa);
        }

        if ($this->convertirAWebp($rutaOrigen, $rutaWebp, $anchoMaximo)) {
            return $this->obtenerUrlWebp($rutaRelativa);
        }

        return null;
    }

    /**
     * Retorna la URL de la versión WebP si existe en disco; en caso contrario retorna null.
     */
    public function obtenerUrlWebp(string $rutaRelativa): ?string
    {
        if (
            $rutaRelativa === ''
            || str_starts_with($rutaRelativa, 'http://')
            || str_starts_with($rutaRelativa, 'https://')
        ) {
            return null;
        }

        if (str_ends_with(strtolower($rutaRelativa), '.webp')) {
            return str_starts_with($rutaRelativa, 'images/')
                ? asset($rutaRelativa)
                : Storage::disk('public')->url($rutaRelativa);
        }

        $rutaBaseWebp = preg_replace('/\.[^.]+$/', '.webp', $rutaRelativa);
        if (! is_string($rutaBaseWebp)) {
            return null;
        }

        if (str_starts_with($rutaRelativa, 'images/')) {
            return is_file(public_path($rutaBaseWebp)) ? asset($rutaBaseWebp) : null;
        }

        return Storage::disk('public')->exists($rutaBaseWebp)
            ? Storage::disk('public')->url($rutaBaseWebp)
            : null;
    }

    private function obtenerRutaWebp(string $rutaOrigen): ?string
    {
        $rutaWebp = preg_replace('/\.[^.]+$/', '.webp', $rutaOrigen);

        return is_string($rutaWebp) ? $rutaWebp : null;
    }

    private function convertirAWebp(string $rutaOrigen, string $rutaDestino, int $anchoMaximo): bool
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return false;
        }

        try {
            $info = @getimagesize($rutaOrigen);
            if ($info === false) {
                return false;
            }

            [$anchoOriginal, $altoOriginal, $tipo] = $info;
            if ($anchoOriginal <= 0 || $altoOriginal <= 0) {
                return false;
            }

            $imagenOrigen = match ($tipo) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($rutaOrigen),
                IMAGETYPE_PNG => @imagecreatefrompng($rutaOrigen),
                IMAGETYPE_WEBP => @imagecreatefromwebp($rutaOrigen),
                default => false,
            };

            if (! $imagenOrigen) {
                return false;
            }

            $anchoDestino = $anchoOriginal;
            $altoDestino = $altoOriginal;

            if ($anchoOriginal > $anchoMaximo) {
                $anchoDestino = $anchoMaximo;
                $altoDestino = (int) round(($altoOriginal * $anchoMaximo) / $anchoOriginal);
            }

            $lienzo = imagecreatetruecolor($anchoDestino, $altoDestino);
            if (! $lienzo) {
                imagedestroy($imagenOrigen);

                return false;
            }

            imagealphablending($lienzo, false);
            imagesavealpha($lienzo, true);
            $transparente = imagecolorallocatealpha($lienzo, 0, 0, 0, 127);
            imagefilledrectangle($lienzo, 0, 0, $anchoDestino, $altoDestino, $transparente);

            imagecopyresampled(
                $lienzo,
                $imagenOrigen,
                0,
                0,
                0,
                0,
                $anchoDestino,
                $altoDestino,
                $anchoOriginal,
                $altoOriginal
            );

            $guardado = @imagewebp($lienzo, $rutaDestino, self::CALIDAD_WEBP);

            imagedestroy($lienzo);
            imagedestroy($imagenOrigen);

            return $guardado;
        } catch (Throwable) {
            return false;
        }
    }
}
