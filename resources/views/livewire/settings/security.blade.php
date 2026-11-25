<section>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Configuración de cuenta</p>
            <h1><span aria-hidden="true">🔒</span> Seguridad de la Cuenta</h1>
            <p>Actualiza tu contraseña de acceso y gestiona la autenticación de dos factores (2FA).</p>
        </div>
    </div>

    <nav class="settings-tabs" aria-label="Opciones de configuración de cuenta">
        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit', 'admin.configuracion.perfil') ? 'is-active' : '' }}">
            👤 Perfil
        </a>
        <a href="{{ route('appearance.edit') }}" class="{{ request()->routeIs('appearance.edit', 'admin.configuracion.apariencia') ? 'is-active' : '' }}">
            🌗 Apariencia
        </a>
        <a href="{{ route('security.edit') }}" class="{{ request()->routeIs('security.edit', 'admin.configuracion.seguridad') ? 'is-active' : '' }}">
            🔒 Seguridad
        </a>
    </nav>

    <div class="settings-grid">
        <div class="card-box">
            <h2>Actualizar contraseña</h2>
            <p style="color: var(--muted);">
                Utiliza una contraseña segura de al menos 8 caracteres para proteger tu acceso al panel administrativo.
            </p>

            @if($mensajeExitoPassword)
                <p class="message success" role="status">{{ $mensajeExitoPassword }}</p>
            @endif

            <form class="content-form" wire:submit="actualizarPassword">
                <label>
                    Contraseña actual
                    <input
                        type="password"
                        wire:model="current_password"
                        autocomplete="current-password"
                        required
                    >
                    @error('current_password') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                </label>

                <label>
                    Nueva contraseña (mínimo 8 caracteres)
                    <input
                        type="password"
                        wire:model="password"
                        autocomplete="new-password"
                        required
                    >
                    @error('password') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                </label>

                <label>
                    Confirmar nueva contraseña
                    <input
                        type="password"
                        wire:model="password_confirmation"
                        autocomplete="new-password"
                        required
                    >
                    @error('password_confirmation') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                </label>

                <button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Actualizar contraseña</span>
                    <span wire:loading>Actualizando...</span>
                </button>
            </form>
        </div>

        @if($soporta2FA)
            <div class="card-box">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: .5rem;">
                    <h2 style="margin: 0;">Autenticación de dos factores (2FA)</h2>
                    <span class="status {{ $twoFactorEnabled ? '' : 'off' }}">
                        {{ $twoFactorEnabled ? 'Activada' : 'Inactiva' }}
                    </span>
                </div>

                <p style="color: var(--muted);">
                    Agrega una capa adicional de seguridad solicitando un código temporal (TOTP) generado por Google Authenticator, Microsoft Authenticator o Authy al iniciar sesión.
                </p>

                @if($mensajeExito2FA)
                    <p class="message success" role="status">{{ $mensajeExito2FA }}</p>
                @endif
                @if($mensajeError2FA)
                    <p class="message" role="alert">{{ $mensajeError2FA }}</p>
                @endif

                @if(! $twoFactorEnabled && ! $mostrandoConfirmacion2FA)
                    <div class="content-form">
                        <label>
                            Confirma tu contraseña actual para habilitar 2FA
                            <input
                                type="password"
                                wire:model="passwordConfirmacion2FA"
                                autocomplete="current-password"
                                placeholder="Ingresa tu contraseña actual"
                            >
                            @error('passwordConfirmacion2FA') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                        </label>

                        <button type="button" class="btn-primary" wire:click="habilitarDosFactores" wire:loading.attr="disabled">
                            Habilitar autenticación de dos factores (2FA)
                        </button>
                    </div>
                @endif

                @if($mostrandoConfirmacion2FA && $qrSvg)
                    <div class="two-factor-setup-box">
                        <p><strong>Paso 1:</strong> Escanea este código QR con tu aplicación autenticadora o ingresa manualmente la clave secreta:</p>

                        <div class="qr-code-container" aria-label="Código QR de autenticación de dos factores">
                            {!! $qrSvg !!}
                        </div>

                        @if($claveSecretaTexto)
                            <p class="secret-key-box">
                                Clave de configuración manual: <code>{{ $claveSecretaTexto }}</code>
                            </p>
                        @endif

                        <form class="content-form" wire:submit="confirmarDosFactores">
                            <label>
                                <strong>Paso 2:</strong> Ingresa el código de 6 dígitos generado por tu aplicación
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    wire:model="codigoConfirmacion2FA"
                                    placeholder="123456"
                                    autocomplete="one-time-code"
                                    required
                                >
                                @error('codigoConfirmacion2FA') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                            </label>

                            <div style="display: flex; gap: .75rem; flex-wrap: wrap;">
                                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                                    Confirmar y activar 2FA
                                </button>
                                <button type="button" class="btn-secondary" wire:click="desactivarDosFactores">
                                    Cancelar configuración
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @if($twoFactorEnabled)
                    <div class="two-factor-active-box">
                        <div style="display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <button type="button" class="btn-secondary" wire:click="mostrarCodigosRecuperacion">
                                {{ $mostrandoCodigosRecuperacion ? 'Ocultar códigos de recuperación' : 'Ver códigos de recuperación' }}
                            </button>
                        </div>

                        @if($mostrandoCodigosRecuperacion && ! empty($codigosRecuperacion))
                            <div class="recovery-codes-box">
                                <strong>Códigos de recuperación de emergencia:</strong>
                                <p style="color: var(--muted); font-size: .84rem; margin: .35rem 0 .75rem;">
                                    Guarda estos códigos en un lugar seguro. Cada código puede utilizarse una sola vez si pierdes acceso a tu teléfono.
                                </p>
                                <div class="recovery-codes-grid">
                                    @foreach($codigosRecuperacion as $codigoRec)
                                        <code>{{ $codigoRec }}</code>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="content-form" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--line);">
                            <label>
                                Confirma tu contraseña actual para regenerar códigos o desactivar 2FA
                                <input
                                    type="password"
                                    wire:model="passwordConfirmacion2FA"
                                    autocomplete="current-password"
                                    placeholder="Ingresa tu contraseña actual"
                                >
                                @error('passwordConfirmacion2FA') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                            </label>

                            <div style="display: flex; gap: .75rem; flex-wrap: wrap;">
                                <button type="button" class="btn-secondary" wire:click="regenerarCodigosRecuperacion">
                                    Regenerar códigos de recuperación
                                </button>
                                <button
                                    type="button"
                                    class="btn-danger"
                                    wire:click="desactivarDosFactores"
                                >
                                    Desactivar 2FA
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
