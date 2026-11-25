<?php

namespace App\Livewire\Autenticacion;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Iniciar sesión | RE/MAX Zona')]
class IniciarSesion extends Component
{
    public string $correo = '';
    public string $password = '';
    public bool $recordar = false;

    public bool $requiereDesafio2FA = false;
    public ?int $usuarioDesafioId = null;
    public string $codigo2FA = '';
    public string $codigoRecuperacion2FA = '';
    public bool $usarCodigoRecuperacion = false;

    public function autenticar(): void
    {
        $this->validate([
            'correo' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ], [
            'correo.required' => 'Ingresa tu correo electrónico.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $claveIntento = Str::lower($this->correo) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($claveIntento, 5)) {
            $segundos = RateLimiter::availableIn($claveIntento);
            $this->addError('correo', "Demasiados intentos. Intenta nuevamente en {$segundos} segundos.");
            return;
        }

        /** @var Usuario|null $usuario */
        $usuario = Usuario::where('correo', $this->correo)->first();

        if (! $usuario || ! Hash::check($this->password, $usuario->password)) {
            RateLimiter::hit($claveIntento, 60);
            $this->addError('correo', 'Las credenciales ingresadas no son válidas.');
            return;
        }

        if (! $usuario->activo) {
            $this->addError('correo', 'Esta cuenta se encuentra desactivada. Contacta al administrador.');
            return;
        }

        RateLimiter::clear($claveIntento);

        // Si el usuario tiene 2FA confirmado y activo, solicitar el segundo factor antes de iniciar sesión
        if ($usuario->hasEnabledTwoFactorAuthentication()) {
            $this->requiereDesafio2FA = true;
            $this->usuarioDesafioId = $usuario->id;
            $this->codigo2FA = '';
            $this->codigoRecuperacion2FA = '';
            $this->usarCodigoRecuperacion = false;
            session([
                'login.id' => $usuario->id,
                'login.remember' => $this->recordar,
            ]);
            return;
        }

        $this->completarInicioSesion($usuario);
    }

    public function verificarDesafio2FA(TwoFactorAuthenticationProvider $provider): void
    {
        $idDesafio = $this->usuarioDesafioId ?? session('login.id');

        /** @var Usuario|null $usuario */
        $usuario = $idDesafio ? Usuario::find($idDesafio) : null;

        if (! $usuario || ! $usuario->activo || ! $usuario->hasEnabledTwoFactorAuthentication()) {
            $this->cancelarDesafio2FA();
            $this->addError('correo', 'La sesión de verificación ha expirado. Inicia sesión nuevamente.');
            return;
        }

        $claveIntento2FA = '2fa|' . $usuario->id . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($claveIntento2FA, 5)) {
            $segundos = RateLimiter::availableIn($claveIntento2FA);
            $this->addError('codigo2FA', "Demasiados intentos de verificación. Espera {$segundos} segundos.");
            return;
        }

        if ($this->usarCodigoRecuperacion) {
            $codigoRec = trim($this->codigoRecuperacion2FA);
            if ($codigoRec === '') {
                $this->addError('codigoRecuperacion2FA', 'Ingresa uno de tus códigos de recuperación.');
                return;
            }

            $coincidencia = collect($usuario->recoveryCodes())->first(
                fn (string $c) => hash_equals($c, $codigoRec)
            );

            if (! $coincidencia) {
                RateLimiter::hit($claveIntento2FA, 60);
                $this->addError('codigoRecuperacion2FA', 'El código de recuperación ingresado no es válido.');
                return;
            }

            $usuario->replaceRecoveryCode($coincidencia);
        } else {
            $codigo = trim($this->codigo2FA);
            if ($codigo === '') {
                $this->addError('codigo2FA', 'Ingresa el código de 6 dígitos de tu aplicación autenticadora.');
                return;
            }

            $secreto = Fortify::currentEncrypter()->decrypt($usuario->two_factor_secret);
            if (! $provider->verify($secreto, $codigo)) {
                RateLimiter::hit($claveIntento2FA, 60);
                $this->addError('codigo2FA', 'El código de autenticación de dos factores no es válido.');
                return;
            }
        }

        RateLimiter::clear($claveIntento2FA);
        session()->forget(['login.id', 'login.remember']);

        $this->completarInicioSesion($usuario);
    }

    public function alternarModoRecuperacion2FA(): void
    {
        $this->usarCodigoRecuperacion = ! $this->usarCodigoRecuperacion;
        $this->resetErrorBag(['codigo2FA', 'codigoRecuperacion2FA']);
    }

    public function cancelarDesafio2FA(): void
    {
        $this->requiereDesafio2FA = false;
        $this->usuarioDesafioId = null;
        $this->codigo2FA = '';
        $this->codigoRecuperacion2FA = '';
        $this->usarCodigoRecuperacion = false;
        session()->forget(['login.id', 'login.remember']);
    }

    private function completarInicioSesion(Usuario $usuario): void
    {
        Auth::login($usuario, $this->recordar);
        session()->regenerate();
        session(['admin_tema' => $usuario->tema ?? Usuario::TEMA_CLARO]);

        $this->redirectRoute('admin.propiedades');
    }

    public function render()
    {
        return view('livewire.autenticacion.iniciar-sesion');
    }
}
