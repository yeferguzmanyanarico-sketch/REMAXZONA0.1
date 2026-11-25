<?php

namespace Tests\Feature;

use App\Livewire\Admin\GestionRankingActual;
use App\Models\PosicionRanking;
use App\Models\RankingMensual;
use App\Models\Usuario;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RankingMensualIndependienteTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_modulo_vendedores_fue_retirado_y_permanecen_agentes_y_ranking(): void
    {
        $marketing = Usuario::factory()->create([
            'rol' => Usuario::ROL_MARKETING,
            'activo' => true,
        ]);

        $this->assertFalse(Route::has('admin.vendedores'));

        $this->actingAs($marketing)
            ->get('/admin/vendedores')
            ->assertNotFound();

        $respuestaAgentes = $this->actingAs($marketing)->get(route('admin.agentes'));
        $respuestaAgentes->assertOk();
        $respuestaAgentes->assertDontSee('/admin/vendedores', false);

        $this->actingAs($marketing)
            ->get(route('admin.ranking.actual'))
            ->assertOk()
            ->assertSee('Gestión del Ranking Mensual');
    }

    public function test_registro_manual_tres_puestos_validaciones_duplicados_y_publicacion(): void
    {
        Storage::fake('public');

        $fechaVentana = CarbonImmutable::create(2026, 11, 2, 10, 0, 0, 'America/Lima');
        Carbon::setTestNow($fechaVentana);
        CarbonImmutable::setTestNow($fechaVentana);

        $marketing = Usuario::factory()->create([
            'rol' => Usuario::ROL_MARKETING,
            'activo' => true,
        ]);

        $this->actingAs($marketing);

        $componente = Livewire::test(GestionRankingActual::class)
            ->assertSee('Pendiente')
            ->assertSee('0 / 3 puestos completados');

        // No permite publicar incompleto
        $componente->call('publicarRanking')
            ->assertSee('Debes completar los tres puestos');

        // Registrar Puesto 1
        $componente
            ->set('puestos.1.nombres', 'Carlos')
            ->set('puestos.1.apellidos', 'Quispe Mamani')
            ->set('puestos.1.celular', '+51 951 111 222')
            ->set('foto1', UploadedFile::fake()->image('p1.jpg', 600, 600))
            ->call('guardarPuesto', 1)
            ->assertHasNoErrors()
            ->assertSee('1 / 3 puestos completados');

        $pos1 = PosicionRanking::where('puesto', 1)->firstOrFail();
        $this->assertNotEmpty($pos1->codigo_interno);
        $this->assertSame('951111222', $pos1->celular_normalizado);
        $this->assertNull($pos1->vendedor_id);
        $this->assertTrue(Storage::disk('public')->exists($pos1->foto_snapshot));
        $fotoOriginalP1 = $pos1->foto_snapshot;

        // Intentar registrar Puesto 2 con el mismo celular en distinto formato -> BLOQUEADO
        $componente
            ->set('puestos.2.nombres', 'María')
            ->set('puestos.2.apellidos', 'Flores Cruz')
            ->set('puestos.2.celular', '951-111-222')
            ->set('foto2', UploadedFile::fake()->image('p2.jpg', 600, 600))
            ->call('guardarPuesto', 2)
            ->assertSee('ya está registrado en el Puesto #1');

        $this->assertDatabaseCount('posiciones_ranking', 1);

        // Registrar Puesto 2 con mismo nombre (homónimo) y distinto celular -> ADVERTENCIA, NO BLOQUEA
        $componente
            ->set('puestos.2.nombres', 'Carlos')
            ->set('puestos.2.apellidos', 'Quispe Mamani')
            ->set('puestos.2.celular', '952 333 444')
            ->set('foto2', UploadedFile::fake()->image('p2.jpg', 600, 600))
            ->call('guardarPuesto', 2)
            ->assertHasNoErrors()
            ->assertSee('homónimo')
            ->assertSee('2 / 3 puestos completados');

        // Editar Puesto 1 reemplazando su foto -> elimina la foto anterior sin dejar huérfanos
        $componente
            ->set('puestos.1.nombres', 'Carlos Alberto')
            ->set('foto1', UploadedFile::fake()->image('p1-nueva.png', 600, 600))
            ->call('guardarPuesto', 1)
            ->assertHasNoErrors();

        $pos1Actualizada = $pos1->fresh();
        $this->assertNotSame($fotoOriginalP1, $pos1Actualizada->foto_snapshot);
        $this->assertFalse(Storage::disk('public')->exists($fotoOriginalP1));
        $this->assertTrue(Storage::disk('public')->exists($pos1Actualizada->foto_snapshot));

        // Registrar Puesto 3 y publicar ranking completo
        $componente
            ->set('puestos.3.nombres', 'Lucía')
            ->set('puestos.3.apellidos', 'Apaza Condori')
            ->set('puestos.3.celular', '953 555 666')
            ->set('foto3', UploadedFile::fake()->image('p3.webp', 600, 600))
            ->call('guardarPuesto', 3)
            ->assertHasNoErrors()
            ->assertSee('3 / 3 puestos completados')
            ->call('publicarRanking')
            ->assertSee('Ranking publicado y actualizado correctamente');

        $this->assertDatabaseHas('rankings_mensuales', [
            'anio_publicacion' => 2026,
            'mes_publicacion' => 11,
            'estado' => RankingMensual::ESTADO_PUBLICADO,
        ]);

        // En otro mes, el mismo celular sí puede volver a registrarse
        $mesSiguiente = CarbonImmutable::create(2026, 12, 2, 10, 0, 0, 'America/Lima');
        Carbon::setTestNow($mesSiguiente);
        CarbonImmutable::setTestNow($mesSiguiente);

        app(\App\Services\ServicioRankingMensual::class)->guardarParticipantePuesto(
            1,
            [
                'nombres' => 'Carlos Alberto',
                'apellidos' => 'Quispe Mamani',
                'celular' => '951111222',
            ],
            $marketing->id,
            UploadedFile::fake()->image('p1-dic.jpg', 600, 600)
        );

        $this->assertDatabaseCount('posiciones_ranking', 4);
    }

    public function test_fuera_de_plazo_no_permite_modificar_el_ranking(): void
    {
        $fechaFueraDePlazo = CarbonImmutable::create(2026, 11, 5, 10, 0, 0, 'America/Lima');
        Carbon::setTestNow($fechaFueraDePlazo);
        CarbonImmutable::setTestNow($fechaFueraDePlazo);

        $admin = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
            'activo' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(GestionRankingActual::class)
            ->assertSee('Plazo cerrado')
            ->set('puestos.1.nombres', 'Prueba')
            ->set('puestos.1.apellidos', 'Bloqueo')
            ->set('puestos.1.celular', '999888777')
            ->call('guardarPuesto', 1)
            ->assertSee('ha finalizado');

        $this->assertDatabaseCount('posiciones_ranking', 0);
    }
}
