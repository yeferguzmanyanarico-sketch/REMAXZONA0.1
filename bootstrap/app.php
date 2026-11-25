<?php

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\VerificarRol;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CabecerasSeguridad::class);
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->redirectUsersTo('/admin/propiedades');
        $middleware->alias([
            'rol' => VerificarRol::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e): void {
            if ($e instanceof QueryException || $e instanceof \PDOException) {
                Log::channel('disponibilidad')->critical('Error crítico de base de datos detectado.', [
                    'excepcion' => $e::class,
                    'codigo' => $e->getCode(),
                ]);
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
