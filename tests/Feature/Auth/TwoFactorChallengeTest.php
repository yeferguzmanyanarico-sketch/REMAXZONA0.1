<?php

namespace Tests\Feature\Auth;

use App\Livewire\Autenticacion\IniciarSesion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_challenge_can_be_completed_with_valid_totp_code(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey(16);

        $user = Usuario::factory()->create([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['REC-CODE-1', 'REC-CODE-2'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $validCode = $google2fa->getCurrentOtp($secret);

        Livewire::test(IniciarSesion::class)
            ->set('correo', $user->correo)
            ->set('password', 'password')
            ->call('autenticar')
            ->assertSet('requiereDesafio2FA', true)
            ->set('codigo2FA', $validCode)
            ->call('verificarDesafio2FA', app(TwoFactorAuthenticationProvider::class))
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.propiedades'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_challenge_can_be_completed_with_recovery_code(): void
    {
        $user = Usuario::factory()->withTwoFactor()->create();

        Livewire::test(IniciarSesion::class)
            ->set('correo', $user->correo)
            ->set('password', 'password')
            ->call('autenticar')
            ->assertSet('requiereDesafio2FA', true)
            ->call('alternarModoRecuperacion2FA')
            ->set('codigoRecuperacion2FA', 'recovery-code-1')
            ->call('verificarDesafio2FA', app(TwoFactorAuthenticationProvider::class))
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.propiedades'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotContains('recovery-code-1', $user->fresh()->recoveryCodes());
    }
}
