<?php

namespace App\Livewire\Admin;

use App\Models\ConfiguracionContacto;
use App\Support\TelefonoPeru;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Configuración de Contacto | RE/MAX Zona')]
class GestionContacto extends Component
{
    public string $nombre_empresa = '';
    public string $telefono_visible = '';
    public string $whatsapp_general = '';
    public string $correo_contacto = '';
    public ?string $direccion = null;
    public string $horario_lunes_viernes = '';
    public string $horario_sabados = '';
    public string $whatsapp_postulacion = '';

    public ?string $mensajeExito = null;

    public function mount(): void
    {
        if (! Auth::user()?->esAdministrador()) {
            abort(403, 'Acceso exclusivo para el Administrador.');
        }

        $config = ConfiguracionContacto::obtenerUnica();

        $this->nombre_empresa = $config->nombre_empresa;
        $this->telefono_visible = TelefonoPeru::quitarPrefijo($config->telefono_visible);
        $this->whatsapp_general = TelefonoPeru::quitarPrefijo($config->whatsapp_general);
        $this->correo_contacto = $config->correo_contacto;
        $this->direccion = $config->direccion;
        $this->horario_lunes_viernes = $config->horario_lunes_viernes;
        $this->horario_sabados = $config->horario_sabados;
        $this->whatsapp_postulacion = TelefonoPeru::quitarPrefijo($config->whatsapp_postulacion);
    }

    public function guardarConfiguracion(): void
    {
        if (! Auth::user()?->esAdministrador()) {
            abort(403);
        }

        $this->mensajeExito = null;

        $this->validate([
            'nombre_empresa' => ['required', 'string', 'max:120'],
            'telefono_visible' => ['required', 'string', 'max:30'],
            'whatsapp_general' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'correo_contacto' => ['required', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'horario_lunes_viernes' => ['required', 'string', 'max:120'],
            'horario_sabados' => ['required', 'string', 'max:120'],
            'whatsapp_postulacion' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
        ], [
            'whatsapp_general.regex' => 'Ingresa un número de WhatsApp válido (ej. 999999999).',
            'whatsapp_postulacion.regex' => 'Ingresa un número de WhatsApp válido (ej. 999999999).',
        ]);

        $config = ConfiguracionContacto::obtenerUnica();
        $config->update([
            'nombre_empresa' => trim($this->nombre_empresa),
            'telefono_visible' => TelefonoPeru::formatearConPrefijo($this->telefono_visible),
            'whatsapp_general' => TelefonoPeru::formatearWhatsapp($this->whatsapp_general),
            'correo_contacto' => trim($this->correo_contacto),
            'direccion' => $this->direccion ? trim($this->direccion) : null,
            'horario_lunes_viernes' => trim($this->horario_lunes_viernes),
            'horario_sabados' => trim($this->horario_sabados),
            'whatsapp_postulacion' => TelefonoPeru::formatearWhatsapp($this->whatsapp_postulacion),
        ]);

        $this->mensajeExito = 'Información global de contacto actualizada correctamente.';
    }

    public function render()
    {
        return view('livewire.admin.gestion-contacto');
    }
}
