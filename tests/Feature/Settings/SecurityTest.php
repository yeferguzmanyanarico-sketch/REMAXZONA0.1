<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Security;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);
    }

    public function test_security_settings_page_can_be_rendered(): void
    {
        $user = Usuario::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'));

        $response->assertOk();
        $response->assertSee('Autenticación de dos factores (2FA)');
        $response->assertSee('Actualizar contraseña');
    }

    public function test_security_settings_page_requires_password_confirmation_when_enabled(): void
    {
        $user = Usuario::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_two_factor_authentication_can_be_enabled_confirmed_and_disabled(): void
    {
        $user = Usuario::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(Security::class)
            ->set('passwordConfirmacion2FA', 'password')
            ->call('habilitarDosFactores')
            ->assertHasNoErrors()
            ->assertSet('mostrandoConfirmacion2FA', true);

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        // Generar código TOTP válido con el secreto recién creado
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $google2fa = new Google2FA();
        $validCode = $google2fa->getCurrentOtp($secret);

        $component
            ->set('codigoConfirmacion2FA', $validCode)
            ->call('confirmarDosFactores')
            ->assertHasNoErrors()
            ->assertSet('twoFactorEnabled', true)
            ->assertSet('mostrandoConfirmacion2FA', false);

        $user->refresh();
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());

        // Desactivar 2FA validando contraseña actual
        $component
            ->set('passwordConfirmacion2FA', 'password')
            ->call('desactivarDosFactores')
            ->assertHasNoErrors()
            ->assertSet('twoFactorEnabled', false);

        $user->refresh();
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_two_factor_authentication_disabled_when_confirmation_abandoned_between_requests(): void
    {
        $user = Usuario::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->actingAs($user);

        $component = Livewire::test(Security::class);

        $component->assertSet('twoFactorEnabled', false);

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function test_password_can_be_updated(): void
    {
        $user = Usuario::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = Livewire::test(Security::class)
            ->set('current_password', 'password')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('updatePassword');

        $response->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = Usuario::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = Livewire::test(Security::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('updatePassword');

        $response->assertHasErrors(['current_password']);
    }
}
