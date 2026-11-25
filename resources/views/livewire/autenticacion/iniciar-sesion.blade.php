<main class="login-screen" aria-labelledby="login-title">
    <section class="login-card">
        <img class="login-logo" src="{{ asset('images/CONTENEDOR.png') }}" alt="RE/MAX Zona">
        <p class="eyebrow">Intranet inmobiliaria</p>

        @if(! $requiereDesafio2FA)
            <h1 id="login-title">Bienvenido</h1>
            <p class="login-copy">Ingresa con tu cuenta autorizada para administrar el contenido del sitio.</p>

            @if($errors->has('correo'))
                <p class="message" role="alert">{{ $errors->first('correo') }}</p>
            @endif

            <form wire:submit="autenticar">
                <label>
                    Correo electrónico
                    <input
                        type="email"
                        wire:model="correo"
                        autocomplete="email"
                        @error('correo') aria-invalid="true" @enderror
                        required
                    >
                    @error('correo')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Contraseña
                    <input
                        type="password"
                        wire:model="password"
                        autocomplete="current-password"
                        @error('password') aria-invalid="true" @enderror
                        required
                    >
                    @error('password')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                </label>

                <button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Iniciar sesión</span>
                    <span wire:loading>Verificando...</span>
                </button>
            </form>
        @else
            <h1 id="login-title">Verificación en dos pasos</h1>
            <p class="login-copy">
                @if(! $usarCodigoRecuperacion)
                    Ingresa el código de 6 dígitos generado por tu aplicación autenticadora para continuar.
                @else
                    Ingresa uno de tus códigos de recuperación de emergencia para acceder a tu cuenta.
                @endif
            </p>

            <form wire:submit="verificarDesafio2FA">
                @if(! $usarCodigoRecuperacion)
                    <label>
                        Código de autenticación (6 dígitos)
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            wire:model="codigo2FA"
                            placeholder="123456"
                            autocomplete="one-time-code"
                            required
                        >
                        @error('codigo2FA')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </label>
                @else
                    <label>
                        Código de recuperación
                        <input
                            type="text"
                            wire:model="codigoRecuperacion2FA"
                            placeholder="Ej. AbCdEfGhIj-KlMnOpQrSt"
                            required
                        >
                        @error('codigoRecuperacion2FA')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </label>
                @endif

                <button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Verificar y acceder</span>
                    <span wire:loading>Verificando...</span>
                </button>

                <div style="display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-top: .25rem;">
                    <button type="button" class="btn-secondary" wire:click="alternarModoRecuperacion2FA">
                        {{ $usarCodigoRecuperacion ? 'Usar código de aplicación (6 dígitos)' : 'Usar un código de recuperación' }}
                    </button>

                    <button type="button" class="btn-secondary" wire:click="cancelarDesafio2FA">
                        Cancelar
                    </button>
                </div>
            </form>
        @endif
    </section>
</main>
