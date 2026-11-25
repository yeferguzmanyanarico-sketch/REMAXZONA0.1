<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PosicionRanking extends Model
{
    protected $table = 'posiciones_ranking';

    protected $fillable = [
        'ranking_mensual_id',
        'puesto',
        'codigo_interno',
        'vendedor_id',
        'nombres_snapshot',
        'apellidos_snapshot',
        'celular_snapshot',
        'celular_normalizado',
        'foto_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'puesto' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $validarRankingEditable = function (PosicionRanking $posicion) {
            $ranking = $posicion->rankingMensual()->first();

            if (! $ranking || ! $ranking->puedeModificarse()) {
                throw new DomainException('No se pueden modificar las posiciones de un ranking cerrado, vencido o fuera del plazo de los 3 primeros días.');
            }

            if (! in_array((int) $posicion->puesto, [1, 2, 3], true)) {
                throw new DomainException('El puesto del ranking solo puede ser 1, 2 o 3.');
            }
        };

        static::creating(function (PosicionRanking $posicion) use ($validarRankingEditable) {
            $validarRankingEditable($posicion);

            if (blank($posicion->codigo_interno)) {
                $posicion->codigo_interno = 'PART-' . strtoupper(Str::random(10));
            }
            if (filled($posicion->celular_snapshot)) {
                $posicion->celular_normalizado = self::normalizarCelular($posicion->celular_snapshot);
            }
        });
        static::updating(function (PosicionRanking $posicion) use ($validarRankingEditable) {
            $validarRankingEditable($posicion);

            if (filled($posicion->celular_snapshot)) {
                $posicion->celular_normalizado = self::normalizarCelular($posicion->celular_snapshot);
            }
        });
        static::deleting($validarRankingEditable);
    }

    /**
     * Normaliza un número de celular conservando solo dígitos (y unificando el prefijo +51 en números peruanos de 9 dígitos).
     */
    public static function normalizarCelular(string $celular): string
    {
        $soloDigitos = preg_replace('/\D+/', '', $celular) ?: '';

        if (strlen($soloDigitos) === 11 && str_starts_with($soloDigitos, '519')) {
            return substr($soloDigitos, 2);
        }

        return $soloDigitos;
    }

    /**
     * Normaliza nombres y apellidos para detectar posibles homónimos en un mismo ranking.
     */
    public static function normalizarNombreCompleto(string $nombres, string $apellidos): string
    {
        $completo = trim($nombres) . ' ' . trim($apellidos);
        $colapsado = preg_replace('/\s+/', ' ', trim($completo)) ?: '';

        return Str::lower(Str::ascii($colapsado));
    }

    public function rankingMensual(): BelongsTo
    {
        return $this->belongsTo(RankingMensual::class, 'ranking_mensual_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    public function getNombreCompletoSnapshotAttribute(): string
    {
        return trim("{$this->nombres_snapshot} {$this->apellidos_snapshot}");
    }

    public function getCelularWhatsappSnapshotAttribute(): string
    {
        $soloDigitos = preg_replace('/[^0-9]/', '', $this->celular_snapshot) ?: '';

        if (strlen($soloDigitos) === 9 && str_starts_with($soloDigitos, '9')) {
            return '51' . $soloDigitos;
        }

        return $soloDigitos;
    }

    public function getUrlFotoSnapshotAttribute(): string
    {
        if (str_starts_with($this->foto_snapshot, 'http://') || str_starts_with($this->foto_snapshot, 'https://')) {
            return $this->foto_snapshot;
        }

        return Storage::disk('public')->url($this->foto_snapshot);
    }
}
