<?php

namespace App\Livewire\Autenticacion;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Confirmar contraseña | RE/MAX Zona')]
class ConfirmarPassword extends Component
{
    public string $password = '';

    public function confirmar(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Ingresa tu contraseña actual para continuar.',
        ]);

        $usuario = Auth::user();

        if (! $usuario || ! Hash::check($this->password, $usuario->password)) {
            $this->addError('password', 'La contraseña ingresada no es correcta.');
            return;
        }

        session()->put('auth.password_confirmed_at', time());

        $this->redirectIntended(default: route('security.edit'));
    }

    public function render()
    {
        return view('livewire.autenticacion.confirmar-password');
    }
}
