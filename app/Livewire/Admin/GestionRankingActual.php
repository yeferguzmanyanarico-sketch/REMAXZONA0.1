<?php

namespace App\Livewire\Admin;

use App\Models\RankingMensual;
use App\Services\ServicioRankingMensual;
use App\Support\TelefonoPeru;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.admin')]
#[Title('Ranking Mensual | RE/MAX Zona')]
class GestionRankingActual extends Component
{
    use WithFileUploads;

    /**
     * @var array<int, array{nombres: string, apellidos: string, celular: string, codigo_interno: ?string, url_foto_actual: ?string, registrado: bool}>
     */
    public array $puestos = [];

    public $foto1 = null;

    public $foto2 = null;

    public $foto3 = null;

    /** @var array<int, ?string> */
    public array $advertenciasHomonimo = [1 => null, 2 => null, 3 => null];

    public ?string $mensajeExito = null;

    public ?string $mensajeError = null;

    public function mount(ServicioRankingMensual $servicio): void
    {
        $usuario = Auth::user();
        if (! $usuario || ! $usuario->activo || (! $usuario->esAdministrador() && ! $usuario->esMarketing())) {
            abort(403, 'No tienes permisos para acceder al módulo de Ranking Mensual.');
        }

        $servicio->sincronizarEstadosVencidos();
        $this->cargarDatosDelPeriodoActual();
    }

    private function cargarDatosDelPeriodoActual(): void
    {
        $periodo = RankingMensual::periodoActual();

        $ranking = RankingMensual::with('posiciones')
            ->where('anio_desempeno', $periodo['anio_desempeno'])
            ->where('mes_desempeno', $periodo['mes_desempeno'])
            ->first();

        $posiciones = $ranking ? $ranking->posiciones->keyBy('puesto') : collect();

        foreach ([1, 2, 3] as $numeroPuesto) {
            $pos = $posiciones->get($numeroPuesto);
            $this->puestos[$numeroPuesto] = [
                'nombres' => $pos?->nombres_snapshot ?? '',
                'apellidos' => $pos?->apellidos_snapshot ?? '',
                'celular' => TelefonoPeru::quitarPrefijo($pos?->celular_snapshot),
                'codigo_interno' => $pos?->codigo_interno,
                'url_foto_actual' => $pos?->url_foto_snapshot,
                'registrado' => $pos !== null,
            ];
        }
    }

    public function limpiarFotoPuesto(int $puesto): void
    {
        if (! in_array($puesto, [1, 2, 3], true)) {
            return;
        }

        $campoFoto = "foto{$puesto}";
        $this->{$campoFoto} = null;
        $this->resetErrorBag($campoFoto);
    }

    public function guardarPuesto(int $puesto, ServicioRankingMensual $servicio): void
    {
        if (! in_array($puesto, [1, 2, 3], true)) {
            return;
        }

        $this->mensajeExito = null;
        $this->mensajeError = null;
        $this->advertenciasHomonimo[$puesto] = null;

        $servicio->sincronizarEstadosVencidos();
        $periodo = RankingMensual::periodoActual();

        if (! RankingMensual::dentroDePlazoDeGestion($periodo['anio_publicacion'], $periodo['mes_publicacion'])) {
            $this->mensajeError = 'El plazo de gestión (días 1 al 3 del mes) ha finalizado. El ranking se encuentra bloqueado.';

            return;
        }

        $campoFoto = "foto{$puesto}";
        $yaRegistrado = (bool) ($this->puestos[$puesto]['registrado'] ?? false);

        $this->validate([
            "puestos.{$puesto}.nombres" => ['required', 'string', 'min:2', 'max:100'],
            "puestos.{$puesto}.apellidos" => ['required', 'string', 'min:2', 'max:100'],
            "puestos.{$puesto}.celular" => ['required', 'string', 'min:6', 'max:20'],
            $campoFoto => $yaRegistrado
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']
                : ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            "puestos.{$puesto}.nombres.required" => 'Ingresa los nombres del participante.',
            "puestos.{$puesto}.nombres.min" => 'Los nombres deben tener al menos 2 caracteres.',
            "puestos.{$puesto}.apellidos.required" => 'Ingresa los apellidos del participante.',
            "puestos.{$puesto}.apellidos.min" => 'Los apellidos deben tener al menos 2 caracteres.',
            "puestos.{$puesto}.celular.required" => 'Ingresa el número de celular del participante.',
            "puestos.{$puesto}.celular.min" => 'El número de celular debe tener al menos 6 caracteres.',
            "{$campoFoto}.required" => 'Debes seleccionar una fotografía especial para este puesto.',
            "{$campoFoto}.image" => 'El archivo seleccionado debe ser una imagen válida.',
            "{$campoFoto}.mimes" => 'La fotografía debe estar en formato JPG, JPEG, PNG o WebP.',
            "{$campoFoto}.max" => 'La fotografía no debe superar los 4 MB.',
        ]);

        try {
            $resultado = $servicio->guardarParticipantePuesto(
                $puesto,
                [
                    'nombres' => (string) ($this->puestos[$puesto]['nombres'] ?? ''),
                    'apellidos' => (string) ($this->puestos[$puesto]['apellidos'] ?? ''),
                    'celular' => TelefonoPeru::formatearConPrefijo((string) ($this->puestos[$puesto]['celular'] ?? '')),
                ],
                (int) Auth::id(),
                $this->{$campoFoto}
            );

            $this->{$campoFoto} = null;
            $this->advertenciasHomonimo[$puesto] = $resultado['advertencia_homonimo'];
            $this->cargarDatosDelPeriodoActual();

            $this->mensajeExito = "Participante del Puesto #{$puesto} guardado correctamente.";
        } catch (DomainException $e) {
            $this->addError("puestos.{$puesto}.celular", $e->getMessage());
            $this->mensajeError = $e->getMessage();
        } catch (Throwable $e) {
            $this->mensajeError = 'Ocurrió un error al guardar el participante: ' . $e->getMessage();
        }
    }

    public function publicarRanking(ServicioRankingMensual $servicio): void
    {
        $this->mensajeExito = null;
        $this->mensajeError = null;

        $servicio->sincronizarEstadosVencidos();

        try {
            $ranking = $servicio->publicarRankingActual((int) Auth::id());
            $this->cargarDatosDelPeriodoActual();
            $this->mensajeExito = 'Ranking publicado y actualizado correctamente en el sitio público.';
        } catch (DomainException $e) {
            $this->mensajeError = $e->getMessage();
        } catch (Throwable $e) {
            $this->mensajeError = 'Ocurrió un error al publicar el ranking: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $periodo = RankingMensual::periodoActual();
        $dentroDePlazo = RankingMensual::dentroDePlazoDeGestion($periodo['anio_publicacion'], $periodo['mes_publicacion']);

        $rankingActual = RankingMensual::with('posiciones')
            ->where('anio_desempeno', $periodo['anio_desempeno'])
            ->where('mes_desempeno', $periodo['mes_desempeno'])
            ->first();

        $nombreMesDesempeno = RankingMensual::NOMBRES_MESES[$periodo['mes_desempeno']] ?? $periodo['mes_desempeno'];
        $nombreMesPublicacion = RankingMensual::NOMBRES_MESES[$periodo['mes_publicacion']] ?? $periodo['mes_publicacion'];

        $totalRegistrados = collect($this->puestos)->where('registrado', true)->count();

        return view('livewire.admin.gestion-ranking-actual', [
            'periodo' => $periodo,
            'dentroDePlazo' => $dentroDePlazo,
            'rankingActual' => $rankingActual,
            'totalRegistrados' => $totalRegistrados,
            'etiquetaDesempeno' => "{$nombreMesDesempeno} {$periodo['anio_desempeno']}",
            'etiquetaPublicacion' => "{$nombreMesPublicacion} {$periodo['anio_publicacion']}",
            'ahoraLima' => now('America/Lima'),
        ]);
    }
}
