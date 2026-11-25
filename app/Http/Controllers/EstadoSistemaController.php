<?php

namespace App\Http\Controllers;

use App\Services\ServicioDisponibilidad;
use Illuminate\Http\JsonResponse;

class EstadoSistemaController extends Controller
{
    public function __invoke(ServicioDisponibilidad $servicioDisponibilidad): JsonResponse
    {
        $resultado = $servicioDisponibilidad->verificarEstado();

        return response()
            ->json($resultado['cuerpo'], $resultado['codigo_http'])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
