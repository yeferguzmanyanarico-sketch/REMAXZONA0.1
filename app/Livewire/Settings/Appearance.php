<?php

namespace App\Livewire\Settings;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Apariencia del Panel | RE/MAX Zona')]
class Appearance extends Component
{
    public string $tema = Usuario::TEMA_CLARO;

    public ?string $mensajeExito = null;

    public function mount(): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();
        $this->tema = in_array($usuario->tema, [Usuario::TEMA_CLARO, Usuario::TEMA_OSCURO], true)
            ? $usuario->tema
            : Usuario::TEMA_CLARO;
    }

    public function seleccionarTema(string $nuevoTema): void
    {
        $this->tema = $nuevoTema;
        $this->guardarApariencia();
    }

    public function guardarApariencia(): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->mensajeExito = null;

        $this->validate([
            'tema' => ['required', 'string', 'in:' . Usuario::TEMA_CLARO . ',' . Usuario::TEMA_OSCURO],
        ], [
            'tema.required' => 'Selecciona un modo de apariencia.',
            'tema.in' => 'El modo seleccionado debe ser Claro u Oscuro.',
        ]);

        $usuario->update([
            'tema' => $this->tema,
        ]);

        session(['admin_tema' => $this->tema]);

        $this->dispatch('tema-actualizado', tema: $this->tema);

        $etiqueta = $this->tema === Usuario::TEMA_OSCURO ? 'Modo oscuro' : 'Modo claro';
        $this->mensajeExito = "Preferencia de apariencia actualizada a «{$etiqueta}». Se conservará en tus próximas sesiones.";
    }

    private function obtenerUsuarioAutenticado(): Usuario
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if (! $usuario || ! $usuario->activo || (! $usuario->esAdministrador() && ! $usuario->esMarketing())) {
            abort(403, 'No tienes permisos para acceder a la configuración de apariencia.');
        }

        return $usuario;
    }

    public function render()
    {
        return view('livewire.settings.appearance', [
            'usuario' => $this->obtenerUsuarioAutenticado(),
        ]);
    }
}
