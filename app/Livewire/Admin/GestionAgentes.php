<?php

namespace App\Livewire\Admin;

use App\Models\Agente;
use App\Models\Usuario;
use App\Support\TelefonoPeru;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.admin')]
#[Title('Gestión de Agentes | RE/MAX Zona')]
class GestionAgentes extends Component
{
    use WithFileUploads;

    public string $busqueda = '';
    public bool $mostrarFormulario = false;

    public ?int $agenteId = null;
    public string $nombres = '';
    public string $apellidos = '';
    public string $biografia = '';
    public string $celular = '';
    public string $whatsapp = '';
    public ?string $correo = null;
    public bool $activo = true;
    public $fotografiaArchivo = null;
    public ?string $fotografiaActual = null;
    public ?string $urlFotografiaActual = null;

    public ?string $mensajeExito = null;
    public ?string $mensajeError = null;

    public function mount(): void
    {
        $this->verificarAutorizacion();
    }

    public function nuevoAgente(): void
    {
        $this->verificarAutorizacion();
        $this->limpiarFormulario();
        $this->mostrarFormulario = true;
    }

    public function editarAgente(int $id): void
    {
        $this->verificarAutorizacion();

        $agente = Agente::findOrFail($id);

        $this->resetValidation();
        $this->mensajeExito = null;
        $this->mensajeError = null;

        $this->agenteId = $agente->id;
        $this->nombres = $agente->nombres;
        $this->apellidos = $agente->apellidos;
        $this->biografia = $agente->biografia;
        $this->celular = TelefonoPeru::quitarPrefijo($agente->celular);
        $this->whatsapp = TelefonoPeru::quitarPrefijo($agente->whatsapp);
        $this->correo = $agente->correo ?? '';
        $this->activo = (bool) $agente->activo;
        $this->fotografiaActual = $agente->fotografia;
        $this->urlFotografiaActual = $agente->url_fotografia;
        $this->fotografiaArchivo = null;
        $this->mostrarFormulario = true;
    }

    public function limpiarFotografiaSeleccionada(): void
    {
        $this->verificarAutorizacion();

        $this->fotografiaArchivo = null;
        $this->resetErrorBag('fotografiaArchivo');
    }

    public function guardarAgente(): void
    {
        $this->verificarAutorizacion();

        $this->mensajeExito = null;
        $this->mensajeError = null;

        $this->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'biografia' => ['required', 'string', 'max:1000'],
            'celular' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,30}$/'],
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,30}$/'],
            'correo' => ['nullable', 'email', 'max:150'],
            'activo' => ['boolean'],
            'fotografiaArchivo' => $this->agenteId
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']
                : ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'nombres.required' => 'Ingresa los nombres del agente.',
            'nombres.max' => 'Los nombres no deben superar los 100 caracteres.',
            'apellidos.required' => 'Ingresa los apellidos del agente.',
            'apellidos.max' => 'Los apellidos no deben superar los 100 caracteres.',
            'biografia.required' => 'La biografía profesional del agente es obligatoria.',
            'biografia.max' => 'La biografía profesional no debe superar los 1000 caracteres.',
            'celular.required' => 'Ingresa el número de celular del agente.',
            'celular.regex' => 'Ingresa un número de celular válido.',
            'whatsapp.required' => 'Ingresa el número de WhatsApp del agente.',
            'whatsapp.regex' => 'Ingresa un número de WhatsApp válido.',
            'correo.email' => 'Ingresa una dirección de correo electrónico válida.',
            'correo.max' => 'El correo electrónico no debe superar los 150 caracteres.',
            'fotografiaArchivo.required' => 'Debes seleccionar una fotografía para registrar al agente.',
            'fotografiaArchivo.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'fotografiaArchivo.mimes' => 'La fotografía debe estar en formato JPG, JPEG, PNG o WEBP.',
            'fotografiaArchivo.max' => 'La fotografía no debe superar los 4 MB.',
        ]);

        $rutaFotografiaNueva = null;
        $rutaFotografia = $this->fotografiaActual;
        $fotografiaAnteriorParaBorrar = null;

        if ($this->fotografiaArchivo) {
            $rutaFotografiaNueva = $this->fotografiaArchivo->store('agentes', 'public');
            app(\App\Services\ServicioOptimizacionImagen::class)->generarWebpDesdeDiscoPublico($rutaFotografiaNueva, 600);
            $rutaFotografia = $rutaFotografiaNueva;

            if (
                $this->agenteId
                && $this->fotografiaActual
                && $this->fotografiaActual !== $rutaFotografia
                && str_starts_with($this->fotografiaActual, 'agentes/')
            ) {
                $fotografiaAnteriorParaBorrar = $this->fotografiaActual;
            }
        }

        $correoNormalizado = $this->correo !== null ? trim($this->correo) : '';

        $datos = [
            'nombres' => trim($this->nombres),
            'apellidos' => trim($this->apellidos),
            'biografia' => trim($this->biografia),
            'celular' => TelefonoPeru::formatearConPrefijo($this->celular),
            'whatsapp' => TelefonoPeru::formatearWhatsapp($this->whatsapp),
            'correo' => $correoNormalizado !== '' ? $correoNormalizado : null,
            'fotografia' => $rutaFotografia,
            'activo' => $this->activo,
        ];

        try {
            if ($this->agenteId) {
                $agente = Agente::findOrFail($this->agenteId);
                $agente->update($datos);
                $this->mensajeExito = 'Datos del agente actualizados correctamente.';
            } else {
                Agente::create($datos);
                $this->mensajeExito = 'Agente registrado correctamente en el directorio.';
            }
        } catch (Throwable $e) {
            if ($rutaFotografiaNueva && Storage::disk('public')->exists($rutaFotografiaNueva)) {
                Storage::disk('public')->delete($rutaFotografiaNueva);
            }

            throw $e;
        }

        if ($fotografiaAnteriorParaBorrar && Storage::disk('public')->exists($fotografiaAnteriorParaBorrar)) {
            Storage::disk('public')->delete($fotografiaAnteriorParaBorrar);
        }

        $this->limpiarFormulario();
    }

    public function alternarEstado(int $id): void
    {
        $this->verificarAutorizacion();

        $agente = Agente::findOrFail($id);
        $nuevoEstado = ! $agente->activo;

        $agente->update([
            'activo' => $nuevoEstado,
        ]);

        if ($this->agenteId === $id) {
            $this->activo = $nuevoEstado;
        }

        $this->mensajeExito = $nuevoEstado
            ? "El agente {$agente->nombre_completo} fue activado y ahora es visible en el sitio público."
            : "El agente {$agente->nombre_completo} fue inactivado y ocultado del sitio público.";
    }

    public function eliminarAgente(int $id): void
    {
        $this->verificarAutorizacion();

        $agente = Agente::findOrFail($id);
        $fotografia = $agente->fotografia;

        $agente->delete();

        if (
            $fotografia
            && str_starts_with($fotografia, 'agentes/')
            && Storage::disk('public')->exists($fotografia)
        ) {
            Storage::disk('public')->delete($fotografia);
        }

        if ($this->agenteId === $id) {
            $this->limpiarFormulario();
        }

        $this->mensajeExito = 'Agente eliminado correctamente del directorio.';
    }

    public function limpiarFormulario(): void
    {
        $this->resetValidation();
        $this->agenteId = null;
        $this->nombres = '';
        $this->apellidos = '';
        $this->biografia = '';
        $this->celular = '';
        $this->whatsapp = '';
        $this->correo = null;
        $this->activo = true;
        $this->fotografiaArchivo = null;
        $this->fotografiaActual = null;
        $this->urlFotografiaActual = null;
        $this->mostrarFormulario = false;
    }

    private function verificarAutorizacion(): void
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if (! $usuario || ! $usuario->activo || (! $usuario->esAdministrador() && ! $usuario->esMarketing())) {
            abort(403, 'No tienes permisos para acceder a la gestión de agentes.');
        }
    }

    public function render()
    {
        $agentes = Agente::query()
            ->when($this->busqueda !== '', function ($query) {
                $termino = '%' . trim($this->busqueda) . '%';
                $query->where(function ($q) use ($termino) {
                    $q->where('nombres', 'like', $termino)
                        ->orWhere('apellidos', 'like', $termino)
                        ->orWhere('celular', 'like', $termino)
                        ->orWhere('whatsapp', 'like', $termino)
                        ->orWhere('correo', 'like', $termino);
                });
            })
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        return view('livewire.admin.gestion-agentes', [
            'agentes' => $agentes,
        ]);
    }
}
