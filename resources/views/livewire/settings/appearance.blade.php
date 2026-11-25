<section>
    @if($mensajeExito)
        <p class="message success" role="status">{{ $mensajeExito }}</p>
    @endif

    <div class="page-heading">
        <div>
            <p class="eyebrow">Configuración de cuenta</p>
            <h1><span aria-hidden="true">🌗</span> Apariencia del Panel</h1>
            <p>Elige entre el modo claro y el modo oscuro para personalizar tu experiencia en el panel administrativo.</p>
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

    <div class="card-box" style="max-width: 760px;">
        <h2>Preferencia de tema visual</h2>
        <p style="color: var(--muted);">
            Tu selección se guarda directamente en tu cuenta de usuario y se aplicará automáticamente cada vez que inicies sesión.
        </p>

        <form class="content-form" wire:submit="guardarApariencia">
            <div class="theme-selector-grid">
                <label class="theme-option-card {{ $tema === 'claro' ? 'is-selected-theme' : '' }}">
                    <input
                        type="radio"
                        name="tema"
                        value="claro"
                        wire:model.live="tema"
                    >
                    <div class="theme-option-preview theme-option-preview--light" aria-hidden="true">
                        <span>☀</span>
                    </div>
                    <div>
                        <strong>Modo claro</strong>
                        <p>Interfaz luminosa con fondo blanco y azul institucional RE/MAX.</p>
                    </div>
                </label>

                <label class="theme-option-card {{ $tema === 'oscuro' ? 'is-selected-theme' : '' }}">
                    <input
                        type="radio"
                        name="tema"
                        value="oscuro"
                        wire:model.live="tema"
                    >
                    <div class="theme-option-preview theme-option-preview--dark" aria-hidden="true">
                        <span>🌙</span>
                    </div>
                    <div>
                        <strong>Modo oscuro</strong>
                        <p>Interfaz de alto contraste oscuro que reduce la fatiga visual.</p>
                    </div>
                </label>
            </div>

            @error('tema') <span class="field-error" role="alert">{{ $message }}</span> @enderror

            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Guardar preferencia de apariencia</span>
                <span wire:loading>Aplicando tema...</span>
            </button>
        </form>
    </div>
</section>
