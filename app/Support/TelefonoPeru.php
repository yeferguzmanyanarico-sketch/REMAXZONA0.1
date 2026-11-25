<?php

namespace App\Support;

class TelefonoPeru
{
    /**
     * Remueve el prefijo +51 o 51 al inicio para mostrar solo el número local junto al prefijo estático +51.
     */
    public static function quitarPrefijo(?string $valor): string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return '';
        }

        if (preg_match('/^\+51[\s\-]*(.+)$/', $texto, $coincidencias)) {
            return trim($coincidencias[1]);
        }

        $soloDigitos = preg_replace('/\D+/', '', $texto) ?: '';
        if (strlen($soloDigitos) >= 11 && str_starts_with($soloDigitos, '51')) {
            return preg_replace('/^51[\s\-]*/', '', $texto) ?: substr($soloDigitos, 2);
        }

        return $texto;
    }

    /**
     * Formatea un teléfono visible asegurando el prefijo +51 sin duplicarlo.
     */
    public static function formatearConPrefijo(?string $valor): string
    {
        $local = self::quitarPrefijo($valor);
        if ($local === '') {
            return '';
        }

        return '+51 ' . $local;
    }

    /**
     * Normaliza un número de WhatsApp para enlaces wa.me/ (dígitos con código de país 51).
     */
    public static function formatearWhatsapp(?string $valor): string
    {
        $local = self::quitarPrefijo($valor);
        $digitos = preg_replace('/\D+/', '', $local) ?: '';

        if ($digitos === '') {
            return '';
        }

        return '51' . $digitos;
    }
}
