<?php

use App\Http\Controllers\EstadoSistemaController;
use App\Livewire\Admin\GestionAgentes;
use App\Livewire\Admin\GestionContacto;
use App\Livewire\Admin\GestionOficinas;
use App\Livewire\Admin\GestionPortada;
use App\Livewire\Admin\GestionPropiedades;
use App\Livewire\Admin\GestionRankingActual;
use App\Livewire\Admin\GestionUsuarios;
use App\Livewire\Admin\HistorialRankings;
use App\Livewire\Autenticacion\IniciarSesion;
use App\Livewire\Publico\PaginaContacto;
use App\Livewire\Publico\PaginaEmpresa;
use App\Livewire\Publico\PaginaInicio;
use App\Livewire\Publico\PaginaOficinas;
use App\Livewire\Publico\PaginaPropiedades;
use App\Livewire\Publico\PaginaPropiedadesOficina;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Sitio Público
Route::get('/', PaginaInicio::class)->name('inicio');
Route::get('/empresa', PaginaEmpresa::class)->name('empresa');
Route::get('/propiedades', PaginaPropiedades::class)->name('propiedades');
Route::get('/oficinas', PaginaOficinas::class)->name('oficinas');
Route::get('/oficinas/{oficina}', PaginaPropiedadesOficina::class)->name('oficinas.propiedades');
Route::get('/contacto', PaginaContacto::class)->name('contacto');
Route::get('/salud', EstadoSistemaController::class)->name('sistema.salud');

Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => route('inicio'), 'changefreq' => 'daily', 'priority' => '1.0'],
        ['loc' => route('empresa'), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['loc' => route('propiedades'), 'changefreq' => 'daily', 'priority' => '0.9'],
        ['loc' => route('oficinas'), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['loc' => route('contacto'), 'changefreq' => 'monthly', 'priority' => '0.7'],
    ];

    foreach (array_keys(Propiedad::SLUGS_OFICINAS) as $slugOficina) {
        $urls[] = [
            'loc' => route('oficinas.propiedades', $slugOficina),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
    }

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $item) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . e($item['loc']) . "</loc>\n";
        $xml .= '    <changefreq>' . $item['changefreq'] . "</changefreq>\n";
        $xml .= '    <priority>' . $item['priority'] . "</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

// Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', IniciarSesion::class)->name('login');
});

Route::post('/admin/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Panel Administrativo
Route::prefix('admin')->middleware(['auth', 'rol:ADMINISTRADOR,MARKETING'])->group(function () {
    Route::redirect('/', '/admin/propiedades');

    // Módulos compartidos entre ADMINISTRADOR y MARKETING
    Route::get('/propiedades', GestionPropiedades::class)->name('admin.propiedades');
    Route::get('/agentes', GestionAgentes::class)->name('admin.agentes');
    Route::get('/ranking-actual', GestionRankingActual::class)->name('admin.ranking.actual');

    // Módulos exclusivos del ADMINISTRADOR
    Route::middleware('rol:ADMINISTRADOR')->group(function () {
        Route::get('/portada', GestionPortada::class)->name('admin.portada');
        Route::get('/oficinas', GestionOficinas::class)->name('admin.oficinas');
        Route::get('/historial-rankings', HistorialRankings::class)->name('admin.ranking.historial');
        Route::get('/configuracion-contacto', GestionContacto::class)->name('admin.contacto');
        Route::get('/usuarios', GestionUsuarios::class)->name('admin.usuarios');
    });
});

require __DIR__.'/settings.php';
