<?php

namespace Tests\Feature\Auth;

use App\Livewire\Autenticacion\IniciarSesion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = Usuario::factory()->create();

        Livewire::test(IniciarSesion::class)
            ->set('correo', $user->correo)
            ->set('password', 'password')
            ->call('autenticar')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.propiedades'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = Usuario::factory()->create();

        Livewire::test(IniciarSesion::class)
            ->set('correo', $user->correo)
            ->set('password', 'wrong-password')
            ->call('autenticar')
            ->assertHasErrors(['correo']);

        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_prompted_for_two_factor_challenge(): void
    {
        $user = Usuario::factory()->withTwoFactor()->create();

        Livewire::test(IniciarSesion::class)
            ->set('correo', $user->correo)
            ->set('password', 'password')
            ->call('autenticar')
            ->assertSet('requiereDesafio2FA', true)
            ->assertSet('usuarioDesafioId', $user->id);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = Usuario::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
