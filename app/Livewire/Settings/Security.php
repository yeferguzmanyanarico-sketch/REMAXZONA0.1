<?php

namespace App\Livewire\Settings;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Seguridad de la Cuenta | RE/MAX Zona')]
class Security extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $twoFactorEnabled = false;
    public bool $mostrandoConfirmacion2FA = false;
    public bool $mostrandoCodigosRecuperacion = false;
    public string $codigoConfirmacion2FA = '';
    public string $passwordConfirmacion2FA = '';

    public ?string $mensajeExitoPassword = null;
    public ?string $mensajeExito2FA = null;
    public ?string $mensajeError2FA = null;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactor): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        // Si quedó un proceso de confirmación 2FA inconcluso de una petición previa, limpiarlo por seguridad
        if (
            Fortify::confirmsTwoFactorAuthentication()
            && ! is_null($usuario->two_factor_secret)
            && is_null($usuario->two_factor_confirmed_at)
        ) {
            $disableTwoFactor($usuario);
            $usuario->refresh();
        }

        $this->sincronizarEstado2FA($usuario);
    }

    public function actualizarPassword(): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->mensajeExitoPassword = null;
        $this->resetErrorBag(['current_password', 'password', 'password_confirmation']);

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'password.required' => 'Ingresa la nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        if (! Hash::check($this->current_password, $usuario->password)) {
            $this->addError('current_password', 'La contraseña actual ingresada no es correcta.');
            return;
        }

        $usuario->update([
            'password' => $this->password,
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->mensajeExitoPassword = 'Tu contraseña ha sido actualizada correctamente.';
    }

    public function updatePassword(): void
    {
        $this->actualizarPassword();
    }

    public function habilitarDosFactores(EnableTwoFactorAuthentication $enableTwoFactor): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->mensajeExito2FA = null;
        $this->mensajeError2FA = null;
        $this->resetErrorBag(['passwordConfirmacion2FA', 'codigoConfirmacion2FA']);

        if (! $this->validarPasswordParaOperacion2FA($usuario)) {
            return;
        }

        $enableTwoFactor($usuario, true);
        $usuario->refresh();

        $this->mostrandoConfirmacion2FA = true;
        $this->mostrandoCodigosRecuperacion = true;
        $this->codigoConfirmacion2FA = '';
        $this->passwordConfirmacion2FA = '';

        $this->sincronizarEstado2FA($usuario);
        $this->mensajeExito2FA = 'Escanea el código QR con tu aplicación de autenticación e ingresa el código de 6 dígitos para confirmar la activación.';
    }

    public function confirmarDosFactores(ConfirmTwoFactorAuthentication $confirmTwoFactor): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->mensajeExito2FA = null;
        $this->mensajeError2FA = null;
        $this->resetErrorBag('codigoConfirmacion2FA');

        $this->validate([
            'codigoConfirmacion2FA' => ['required', 'string', 'digits:6'],
        ], [
            'codigoConfirmacion2FA.required' => 'Ingresa el código de 6 dígitos generado por tu aplicación.',
            'codigoConfirmacion2FA.digits' => 'El código de verificación debe tener exactamente 6 dígitos.',
        ]);

        try {
            $confirmTwoFactor($usuario, trim($this->codigoConfirmacion2FA));
        } catch (ValidationException) {
            $this->addError('codigoConfirmacion2FA', 'El código de autenticación de dos factores ingresado no es válido.');
            return;
        }

        $usuario->refresh();
        $this->mostrandoConfirmacion2FA = false;
        $this->mostrandoCodigosRecuperacion = true;
        $this->codigoConfirmacion2FA = '';

        $this->sincronizarEstado2FA($usuario);
        $this->mensajeExito2FA = 'La autenticación de dos factores (2FA) ha sido confirmada y activada en tu cuenta.';
    }

    public function regenerarCodigosRecuperacion(GenerateNewRecoveryCodes $generateRecoveryCodes): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->mensajeExito2FA = null;
        $this->mensajeError2FA = null;

        if (! $usuario->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        if (! $this->validarPasswordParaOperacion2FA($usuario)) {
            return;
        }

        $generateRecoveryCodes($usuario);
        $usuario->refresh();

        $this->mostrandoCodigosRecuperacion = true;
        $this->passwordConfirmacion2FA = '';
        $this->mensajeExito2FA = 'Se han generado nuevos códigos de recuperación. Guárdalos en un lugar seguro.';
    }

    public function mostrarCodigosRecuperacion(): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        if ($usuario->hasEnabledTwoFactorAuthentication()) {
            $this->mostrandoCodigosRecuperacion = ! $this->mostrandoCodigosRecuperacion;
        }
    }

    public function desactivarDosFactores(DisableTwoFactorAuthentication $disableTwoFactor): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        $this->mensajeExito2FA = null;
        $this->mensajeError2FA = null;
        $this->resetErrorBag(['passwordConfirmacion2FA', 'codigoConfirmacion2FA']);

        // Si ya estaba confirmado y activo, exigir la contraseña actual para desactivarlo
        if ($usuario->hasEnabledTwoFactorAuthentication() && ! $this->validarPasswordParaOperacion2FA($usuario)) {
            return;
        }

        $disableTwoFactor($usuario);
        $usuario->refresh();

        $this->mostrandoConfirmacion2FA = false;
        $this->mostrandoCodigosRecuperacion = false;
        $this->codigoConfirmacion2FA = '';
        $this->passwordConfirmacion2FA = '';

        $this->sincronizarEstado2FA($usuario);
        $this->mensajeExito2FA = 'La autenticación de dos factores (2FA) ha sido desactivada.';
    }

    private function validarPasswordParaOperacion2FA(Usuario $usuario): bool
    {
        if (blank($this->passwordConfirmacion2FA)) {
            $this->addError('passwordConfirmacion2FA', 'Ingresa tu contraseña actual para autorizar esta operación de seguridad.');
            return false;
        }

        if (! Hash::check($this->passwordConfirmacion2FA, $usuario->password)) {
            $this->addError('passwordConfirmacion2FA', 'La contraseña ingresada no coincide con tu contraseña actual.');
            return false;
        }

        return true;
    }

    private function sincronizarEstado2FA(Usuario $usuario): void
    {
        $this->twoFactorEnabled = $usuario->hasEnabledTwoFactorAuthentication();
    }

    private function obtenerUsuarioAutenticado(): Usuario
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if (! $usuario || ! $usuario->activo || (! $usuario->esAdministrador() && ! $usuario->esMarketing())) {
            abort(403, 'No tienes permisos para acceder a la configuración de seguridad.');
        }

        return $usuario;
    }

    public function render()
    {
        $usuario = $this->obtenerUsuarioAutenticado();
        $soporta2FA = Features::enabled(Features::twoFactorAuthentication());

        $qrSvg = null;
        $claveSecretaTexto = null;
        $codigosRecuperacion = [];

        if ($soporta2FA && ! empty($usuario->two_factor_secret)) {
            $qrSvg = $usuario->twoFactorQrCodeSvg();
            $claveSecretaTexto = Fortify::currentEncrypter()->decrypt($usuario->two_factor_secret);

            if ($this->mostrandoCodigosRecuperacion && ! empty($usuario->two_factor_recovery_codes)) {
                $codigosRecuperacion = $usuario->recoveryCodes();
            }
        }

        return view('livewire.settings.security', [
            'usuario' => $usuario,
            'soporta2FA' => $soporta2FA,
            'qrSvg' => $qrSvg,
            'claveSecretaTexto' => $claveSecretaTexto,
            'codigosRecuperacion' => $codigosRecuperacion,
        ]);
    }
}
