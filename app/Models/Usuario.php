<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'usuarios';

    public const ROL_ADMINISTRADOR = 'ADMINISTRADOR';
    public const ROL_MARKETING = 'MARKETING';

    public const TEMA_CLARO = 'claro';
    public const TEMA_OSCURO = 'oscuro';

    protected $fillable = [
        'nombre',
        'correo',
        'password',
        'rol',
        'activo',
        'tema',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esMarketing(): bool
    {
        return $this->rol === self::ROL_MARKETING;
    }

    public function usaTemaOscuro(): bool
    {
        return $this->tema === self::TEMA_OSCURO;
    }

    public function rankingsCreados(): HasMany
    {
        return $this->hasMany(RankingMensual::class, 'creado_por');
    }

    public function rankingsActualizados(): HasMany
    {
        return $this->hasMany(RankingMensual::class, 'actualizado_por');
    }
}
