<?php

namespace Tests\Feature\Auth;

use App\Livewire\Autenticacion\ConfirmarPassword;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = Usuario::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertOk();
    }

    public function test_password_can_be_confirmed(): void
    {
        $user = Usuario::factory()->create();

        $this->actingAs($user);

        Livewire::test(ConfirmarPassword::class)
            ->set('password', 'password')
            ->call('confirmar')
            ->assertHasNoErrors();

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }
}
