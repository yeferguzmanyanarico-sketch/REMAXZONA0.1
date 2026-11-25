<?php

namespace Tests\Feature;

use App\Livewire\Admin\GestionAgentes;
use App\Models\Agente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GestionAgentesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_marketing_can_access_agents_management_page(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $marketing = Usuario::factory()->marketing()->create();

        $this->actingAs($admin)->get(route('admin.agentes'))->assertOk();
        $this->actingAs($marketing)->get(route('admin.agentes'))->assertOk();
    }

    public function test_guests_cannot_access_agents_management_page(): void
    {
        $this->get(route('admin.agentes'))->assertRedirect(route('login'));
    }

    public function test_can_create_edit_toggle_search_and_delete_agent_with_photo(): void
    {
        Storage::fake('public');

        $usuario = Usuario::factory()->marketing()->create();
        $this->actingAs($usuario);

        $fotoInicial = UploadedFile::fake()->image('agente1.jpg', 400, 400);

        // 1. Registrar nuevo agente
        Livewire::test(GestionAgentes::class)
            ->call('nuevoAgente')
            ->set('nombres', 'Carlos Alberto')
            ->set('apellidos', 'Mendoza Ríos')
            ->set('biografia', 'Especialista en inmuebles residenciales y comerciales en Arequipa y Puno.')
            ->set('celular', '987 654 321')
            ->set('whatsapp', '987654321')
            ->set('correo', 'cmendoza@remaxzona.com')
            ->set('activo', true)
            ->set('fotografiaArchivo', $fotoInicial)
            ->call('guardarAgente')
            ->assertHasNoErrors();

        $agente = Agente::first();
        $this->assertNotNull($agente);
        $this->assertEquals('Carlos Alberto Mendoza Ríos', $agente->nombre_completo);
        $this->assertEquals('https://wa.me/51987654321', $agente->whatsapp_enlace);
        Storage::disk('public')->assertExists($agente->fotografia);

        $rutaFotoOriginal = $agente->fotografia;

        // 2. Buscar por nombre y celular
        Livewire::test(GestionAgentes::class)
            ->set('busqueda', 'Mendoza')
            ->assertSee('Carlos Alberto Mendoza Ríos')
            ->set('busqueda', '999999999')
            ->assertDontSee('Carlos Alberto Mendoza Ríos');

        // 3. Editar datos y reemplazar fotografía
        $fotoNueva = UploadedFile::fake()->image('agente2.png', 500, 500);

        Livewire::test(GestionAgentes::class)
            ->call('editarAgente', $agente->id)
            ->set('nombres', 'Carlos A.')
            ->set('biografia', 'Biografía actualizada con más de 10 años de experiencia inmobiliaria.')
            ->set('fotografiaArchivo', $fotoNueva)
            ->call('guardarAgente')
            ->assertHasNoErrors();

        $agente->refresh();
        $this->assertEquals('Carlos A.', $agente->nombres);
        Storage::disk('public')->assertMissing($rutaFotoOriginal);
        Storage::disk('public')->assertExists($agente->fotografia);

        // 4. Alternar estado activo / inactivo
        Livewire::test(GestionAgentes::class)
            ->call('alternarEstado', $agente->id);

        $this->assertFalse($agente->fresh()->activo);

        // 5. Eliminar agente y limpiar su fotografía del disco
        $fotoParaBorrar = $agente->fresh()->fotografia;

        Livewire::test(GestionAgentes::class)
            ->call('eliminarAgente', $agente->id);

        $this->assertDatabaseMissing('agentes', ['id' => $agente->id]);
        Storage::disk('public')->assertMissing($fotoParaBorrar);
    }

    public function test_public_company_page_displays_only_active_agents_separated_from_ranking(): void
    {
        Agente::create([
            'nombres' => 'Lucía Fernanda',
            'apellidos' => 'Paredes Soto',
            'fotografia' => 'agentes/lucia.jpg',
            'biografia' => 'Asesora experta en inversiones inmobiliarias en el sur del Perú.',
            'celular' => '+51 951 222 333',
            'whatsapp' => '951222333',
            'correo' => 'lparedes@remaxzona.com',
            'activo' => true,
        ]);

        Agente::create([
            'nombres' => 'Agente Oculto',
            'apellidos' => 'Inactivo',
            'fotografia' => 'agentes/oculto.jpg',
            'biografia' => 'Este agente está inactivo y no debe mostrarse.',
            'celular' => '900 111 222',
            'whatsapp' => '900111222',
            'correo' => null,
            'activo' => false,
        ]);

        $response = $this->get(route('empresa'));

        $response->assertOk();
        $response->assertSee('Nuestros destacados');
        $response->assertSee('Nuestros agentes inmobiliarios');
        $response->assertSee('Lucía Fernanda Paredes Soto');
        $response->assertSee('Asesora experta en inversiones inmobiliarias en el sur del Perú.');
        $response->assertSee('lparedes@remaxzona.com');
        $response->assertDontSee('Agente Oculto Inactivo');
    }
}
