<?php

namespace Tests\Feature;

use App\Models\Propiedad;
use App\Models\Usuario;
use App\Services\ServicioOptimizacionImagen;
use App\Services\ServicioRespaldo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequerimientosNoFuncionalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rnf025_accesibilidad_en_layouts_publico_y_admin(): void
    {
        $respuestaPublica = $this->get(route('inicio'));
        $respuestaPublica->assertOk();
        $respuestaPublica->assertSee('class="skip-link"', false);
        $respuestaPublica->assertSee('aria-label="Navegación principal"', false);
        $respuestaPublica->assertSee('aria-current="page"', false);

        $admin = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
            'activo' => true,
        ]);

        $respuestaAdmin = $this->actingAs($admin)->get(route('admin.propiedades'));
        $respuestaAdmin->assertOk();
        $respuestaAdmin->assertSee('href="#contenido-admin"', false);
        $respuestaAdmin->assertSee('id="contenido-admin"', false);
        $respuestaAdmin->assertSee('aria-current="page"', false);
    }

    public function test_rnf026_seo_metadatos_y_sitemap_xml(): void
    {
        Propiedad::create([
            'nombre' => 'Casa en Puno Centro',
            'ubicacion' => 'Puno',
            'moneda' => Propiedad::MONEDA_DOLARES,
            'precio' => 150000,
            'enlace_remax' => 'https://www.remax.pe/ejemplo',
            'imagen' => 'images/fondoejemplo.jpg',
            'oficina' => Propiedad::OFICINA_PUNO,
            'destacada' => true,
            'activo' => true,
        ]);

        $respuestaPropiedades = $this->get(route('propiedades'));
        $respuestaPropiedades->assertOk();
        $respuestaPropiedades->assertSee('<meta name="description"', false);
        $respuestaPropiedades->assertSee('<link rel="canonical"', false);
        $respuestaPropiedades->assertSee('property="og:title"', false);
        $respuestaPropiedades->assertSee('application/ld+json', false);
        $respuestaPropiedades->assertSee('ItemList', false);

        $sitemap = $this->get(route('sitemap'));
        $sitemap->assertOk();
        $this->assertStringContainsString('application/xml', (string) $sitemap->headers->get('Content-Type'));
        $sitemap->assertSee('<urlset', false);
        $sitemap->assertSee(route('propiedades'), false);
        $sitemap->assertSee(route('oficinas.propiedades', 'puno'), false);
    }

    public function test_rnf027_endpoint_salud_responde_operativo_sin_exponer_credenciales(): void
    {
        $respuesta = $this->get(route('sistema.salud'));

        $respuesta->assertOk();
        $respuesta->assertJson([
            'estado' => 'operativo',
            'servicios' => [
                'aplicacion' => 'ok',
                'base_datos' => 'ok',
                'almacenamiento' => 'ok',
            ],
        ]);
        $this->assertStringContainsString('no-store', (string) $respuesta->headers->get('Cache-Control'));
        $respuesta->assertDontSee('DB_PASSWORD');
    }

    public function test_rnf028_respaldo_privado_y_verificacion_de_integridad(): void
    {
        $servicio = app(ServicioRespaldo::class);
        $resultado = $servicio->ejecutarRespaldo();

        try {
            $this->assertDirectoryExists($resultado['directorio']);
            $this->assertFileExists($resultado['directorio'] . DIRECTORY_SEPARATOR . 'multimedia.zip');
            $this->assertFileExists($resultado['directorio'] . DIRECTORY_SEPARATOR . 'manifiesto.json');
            $this->assertStringContainsString(
                'storage/app/private/respaldos',
                str_replace('\\', '/', $resultado['directorio'])
            );

            $verificacion = $servicio->verificarIntegridad($resultado['directorio']);
            $this->assertTrue($verificacion['valido']);
        } finally {
            File::deleteDirectory($resultado['directorio']);
        }
    }

    public function test_rnf029_cabeceras_seguridad_y_control_de_roles(): void
    {
        $respuesta = $this->get(route('inicio'));
        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->assertHeader('Content-Security-Policy');

        $marketing = Usuario::factory()->create([
            'rol' => Usuario::ROL_MARKETING,
            'activo' => true,
        ]);

        $this->actingAs($marketing)->get(route('admin.propiedades'))->assertOk();
        $this->actingAs($marketing)->get(route('admin.usuarios'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.portada'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.oficinas'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.contacto'))->assertForbidden();
    }

    public function test_rnf030_optimizacion_webp_conservando_original(): void
    {
        Storage::fake('public');

        $imagenFalsa = UploadedFile::fake()->image('casa-prueba.jpg', 1600, 1200);
        $ruta = $imagenFalsa->store('propiedades', 'public');

        $servicio = app(ServicioOptimizacionImagen::class);
        $urlWebp = $servicio->generarWebpDesdeDiscoPublico($ruta, 1200);

        $this->assertNotNull($urlWebp);
        $this->assertTrue(Storage::disk('public')->exists($ruta));
        $this->assertNotNull($servicio->obtenerUrlWebp($ruta));
    }
}
