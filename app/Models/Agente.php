<?php

namespace App\Models;

use App\Support\TelefonoPeru;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Agente extends Model
{
    protected $table = 'agentes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'fotografia',
        'biografia',
        'celular',
        'whatsapp',
        'correo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function getUrlFotografiaAttribute(): string
    {
        if (str_starts_with($this->fotografia, 'http://') || str_starts_with($this->fotografia, 'https://')) {
            return $this->fotografia;
        }

        if (str_starts_with($this->fotografia, 'images/')) {
            return asset($this->fotografia);
        }

        return Storage::disk('public')->url($this->fotografia);
    }

    public function getCelularEnlaceAttribute(): string
    {
        $limpio = preg_replace('/[^0-9+]/', '', $this->celular) ?: '';

        return $limpio !== '' ? 'tel:' . $limpio : '#';
    }

    public function getWhatsappNumeroLimpioAttribute(): string
    {
        $soloDigitos = preg_replace('/[^0-9]/', '', $this->whatsapp) ?: '';

        if (strlen($soloDigitos) === 9 && str_starts_with($soloDigitos, '9')) {
            return '51' . $soloDigitos;
        }

        return $soloDigitos;
    }

    public function getWhatsappEnlaceAttribute(): string
    {
        return 'https://wa.me/' . $this->whatsapp_numero_limpio;
    }

    public function getWhatsappVisibleAttribute(): string
    {
        return TelefonoPeru::formatearConPrefijo($this->whatsapp);
    }
}
