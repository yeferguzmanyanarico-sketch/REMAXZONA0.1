<?php

namespace Tests\Feature;

use App\Livewire\Admin\GestionPropiedades;
use App\Models\Propiedad;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GestionPropiedadesMasterDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_flujo_completo_master_detail_en_gestion_de_propiedades(): void
    {
        Storage::fake('public');

        $admin = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
            'activo' => true,
        ]);

        $propiedadExistente = Propiedad::create([
            'nombre' => 'Casa Lago Titicaca',
            'ubicacion' => 'Puno',
            'oficina' => Propiedad::OFICINA_PUNO,
            'moneda' => Propiedad::MONEDA_DOLARES,
            'precio' => 180000,
            'enlace_remax' => 'https://www.remax.pe/casa-titicaca',
            'imagen' => 'images/fondoejemplo.jpg',
            'destacada' => false,
            'activo' => true,
        ]);

        $this->actingAs($admin);

        // 1. Estado inicial + búsqueda
        $componente = Livewire::test(GestionPropiedades::class)
            ->assertSet('mostrarFormulario', false)
            ->assertSee('Selecciona una propiedad para editarla')
            ->assertSee('Casa Lago Titicaca');

        // 2. Abrir formulario de registro ("Nueva propiedad")
        $componente->call('nuevaPropiedad')
            ->assertSet('mostrarFormulario', true)
            ->assertSet('propiedadId', null)
            ->assertSee('Nueva propiedad')
            ->assertSee('Cancelar')
            ->assertSee('Volver al listado');

        // 3. Errores de validación mantienen el formulario abierto
        $componente->set('nombre', '')
            ->call('guardarPropiedad')
            ->assertHasErrors(['nombre', 'ubicacion', 'oficina', 'enlace_remax', 'imagenArchivo'])
            ->assertSet('mostrarFormulario', true);

        // 4. Cancelar no crea registros
        $componente->call('limpiarFormulario')
            ->assertSet('mostrarFormulario', false);
        $this->assertDatabaseCount('propiedades', 1);

        // 5. Registrar nueva propiedad y conservar búsqueda
        $componente->set('busqueda', 'Departamento')
            ->call('nuevaPropiedad')
            ->set('nombre', 'Departamento Central Cusco')
            ->set('ubicacion', 'Cusco')
            ->set('oficina', Propiedad::OFICINA_AREQUIPA)
            ->set('moneda', Propiedad::MONEDA_DOLARES)
            ->set('precio', '95000')
            ->set('enlace_remax', 'https://www.remax.pe/depto-cusco')
            ->set('imagenArchivo', UploadedFile::fake()->image('depto.jpg', 800, 600))
            ->call('guardarPropiedad')
            ->assertHasNoErrors()
            ->assertSet('mostrarFormulario', false)
            ->assertSet('busqueda', 'Departamento')
            ->assertSee('Departamento Central Cusco');

        $this->assertDatabaseCount('propiedades', 2);

        // 6. Editar propiedad existente sin duplicar registros y cancelar sin alterar datos
        $componente->set('busqueda', '')
            ->call('editarPropiedad', $propiedadExistente->id)
            ->assertSet('mostrarFormulario', true)
            ->assertSet('propiedadId', $propiedadExistente->id)
            ->assertSee('Editar propiedad')
            ->assertSee('ID #' . $propiedadExistente->id)
            ->set('nombre', 'Cambio Temporal Sin Guardar')
            ->call('limpiarFormulario')
            ->assertSet('mostrarFormulario', false);

        $this->assertDatabaseHas('propiedades', [
            'id' => $propiedadExistente->id,
            'nombre' => 'Casa Lago Titicaca',
        ]);

        // 7. Guardar edición y verificar acciones de destacar y eliminar
        $componente->call('editarPropiedad', $propiedadExistente->id)
            ->set('nombre', 'Casa Lago Titicaca Renovada')
            ->call('guardarPropiedad')
            ->assertHasNoErrors()
            ->assertSet('mostrarFormulario', false);

        $this->assertDatabaseCount('propiedades', 2);
        $this->assertDatabaseHas('propiedades', [
            'id' => $propiedadExistente->id,
            'nombre' => 'Casa Lago Titicaca Renovada',
        ]);

        $componente->call('alternarDestacada', $propiedadExistente->id);
        $this->assertTrue((bool) $propiedadExistente->fresh()->destacada);

        $componente->call('eliminarPropiedad', $propiedadExistente->id);
        $this->assertDatabaseMissing('propiedades', ['id' => $propiedadExistente->id]);
    }
}
