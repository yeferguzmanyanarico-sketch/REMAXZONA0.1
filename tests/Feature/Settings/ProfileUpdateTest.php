<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Profile;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed_for_admin_and_marketing(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $marketing = Usuario::factory()->marketing()->create();

        $this->actingAs($admin)->get('/settings/profile')->assertOk();
        $this->actingAs($marketing)->get('/settings/profile')->assertOk();
        $this->actingAs($marketing)->get('/admin/configuracion/perfil')->assertOk();
    }

    public function test_profile_name_can_be_updated_without_current_password(): void
    {
        $user = Usuario::factory()->create([
            'nombre' => 'Nombre Original',
            'correo' => 'original@remaxzona.com',
        ]);

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('nombre', 'Nombre Actualizado')
            ->set('correo', 'original@remaxzona.com')
            ->call('actualizarPerfil')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Nombre Actualizado', $user->nombre);
        $this->assertEquals('original@remaxzona.com', $user->correo);
    }

    public function test_updating_email_requires_valid_current_password_and_uniqueness(): void
    {
        Usuario::factory()->create(['correo' => 'ocupado@remaxzona.com']);
        $user = Usuario::factory()->create(['correo' => 'actual@remaxzona.com']);

        $this->actingAs($user);

        // Intento de cambiar correo sin contraseña actual
        Livewire::test(Profile::class)
            ->set('nombre', $user->nombre)
            ->set('correo', 'nuevo@remaxzona.com')
            ->set('password_actual', '')
            ->call('actualizarPerfil')
            ->assertHasErrors(['password_actual']);

        // Intento con contraseña actual incorrecta
        Livewire::test(Profile::class)
            ->set('nombre', $user->nombre)
            ->set('correo', 'nuevo@remaxzona.com')
            ->set('password_actual', 'clave-incorrecta')
            ->call('actualizarPerfil')
            ->assertHasErrors(['password_actual']);

        // Intento con correo duplicado
        Livewire::test(Profile::class)
            ->set('nombre', $user->nombre)
            ->set('correo', 'ocupado@remaxzona.com')
            ->set('password_actual', 'password')
            ->call('actualizarPerfil')
            ->assertHasErrors(['correo']);

        // Cambio exitoso con contraseña actual válida
        Livewire::test(Profile::class)
            ->set('nombre', 'Usuario Renovado')
            ->set('correo', 'nuevo@remaxzona.com')
            ->set('password_actual', 'password')
            ->call('actualizarPerfil')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Usuario Renovado', $user->nombre);
        $this->assertEquals('nuevo@remaxzona.com', $user->correo);
    }

    public function test_appearance_theme_preference_can_be_saved_and_persisted(): void
    {
        $user = Usuario::factory()->create(['tema' => Usuario::TEMA_CLARO]);

        $this->actingAs($user);

        Livewire::test(Appearance::class)
            ->set('tema', Usuario::TEMA_OSCURO)
            ->call('guardarApariencia')
            ->assertHasNoErrors();

        $this->assertEquals(Usuario::TEMA_OSCURO, $user->fresh()->tema);
        $this->assertTrue($user->fresh()->usaTemaOscuro());

        // Verificar que el layout administrativo aplica la clase dark-mode
        $this->actingAs($user->fresh())
            ->get(route('appearance.edit'))
            ->assertOk()
            ->assertSee('dark-mode', false);
    }
}
