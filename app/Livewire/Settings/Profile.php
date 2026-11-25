<?php

namespace App\Livewire\Settings;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Perfil de Usuario | RE/MAX Zona')]
class Profile extends Component
{
    public string $nombre = '';
    public string $correo = '';
    public string $password_actual = '';

    // Alias de compatibilidad con convenciones de Livewire Settings
    public string $name = '';
    public string $email = '';

    public ?string $mensajeExito = null;
    public ?string $mensajeError = null;

    public function mount(): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->nombre = $usuario->nombre;
        $this->correo = $usuario->correo;
        $this->name = $usuario->nombre;
        $this->email = $usuario->correo;
    }

    public function updatedName(string $valor): void
    {
        $this->nombre = $valor;
    }

    public function updatedEmail(string $valor): void
    {
        $this->correo = $valor;
    }

    public function updatedNombre(string $valor): void
    {
        $this->name = $valor;
    }

    public function updatedCorreo(string $valor): void
    {
        $this->email = $valor;
    }

    public function actualizarPerfil(): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->mensajeExito = null;
        $this->mensajeError = null;

        $correoNormalizado = strtolower(trim($this->correo));
        $cambioCorreoSensible = $correoNormalizado !== strtolower($usuario->correo);

        $reglas = [
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'correo' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('usuarios', 'correo')->ignore($usuario->id),
            ],
            'password_actual' => $cambioCorreoSensible
                ? ['required', 'string']
                : ['nullable', 'string'],
        ];

        $this->validate($reglas, [
            'nombre.required' => 'Ingresa tu nombre completo.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 120 caracteres.',
            'correo.required' => 'Ingresa tu correo electrónico.',
            'correo.email' => 'Ingresa una dirección de correo electrónico válida.',
            'correo.max' => 'El correo electrónico no debe superar los 150 caracteres.',
            'correo.unique' => 'Ya existe otra cuenta registrada con ese correo electrónico.',
            'password_actual.required' => 'Para cambiar tu correo electrónico debes confirmar tu contraseña actual.',
        ]);

        if ($cambioCorreoSensible || filled($this->password_actual)) {
            if (! Hash::check($this->password_actual, $usuario->password)) {
                $this->addError('password_actual', 'La contraseña actual ingresada no es correcta.');
                return;
            }
        }

        $usuario->update([
            'nombre' => trim($this->nombre),
            'correo' => $correoNormalizado,
        ]);

        $this->nombre = $usuario->nombre;
        $this->correo = $usuario->correo;
        $this->name = $usuario->nombre;
        $this->email = $usuario->correo;
        $this->password_actual = '';

        $this->mensajeExito = 'Información de tu perfil actualizada correctamente.';
    }

    public function updateProfileInformation(): void
    {
        $this->actualizarPerfil();
    }

    private function obtenerUsuarioAutenticado(): Usuario
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if (! $usuario || ! $usuario->activo || (! $usuario->esAdministrador() && ! $usuario->esMarketing())) {
            abort(403, 'No tienes permisos para acceder a la configuración del perfil.');
        }

        return $usuario;
    }

    public function render()
    {
        return view('livewire.settings.profile', [
            'usuario' => $this->obtenerUsuarioAutenticado(),
        ]);
    }
}
