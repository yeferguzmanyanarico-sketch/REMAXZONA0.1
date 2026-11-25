<section>
    @if($mensajeExito)
        <p class="message success" role="status">{{ $mensajeExito }}</p>
    @endif
    @if($mensajeError)
        <p class="message" role="alert">{{ $mensajeError }}</p>
    @endif

    <div class="page-heading">
        <div>
            <p class="eyebrow">Configuración de cuenta</p>
            <h1><span aria-hidden="true">👤</span> Perfil de Usuario</h1>
            <p>Visualiza y actualiza tu información personal y tu dirección de correo electrónico de acceso.</p>
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
        <div class="readonly-meta-grid" style="margin-bottom: 1.25rem;">
            <div>
                <span class="readonly-meta-label">Rol asignado</span>
                <strong>{{ $usuario->rol }}</strong>
            </div>
            <div>
                <span class="readonly-meta-label">Estado de la cuenta</span>
                <strong>{{ $usuario->activo ? 'Activa' : 'Suspendida' }}</strong>
            </div>
        </div>

        <form class="content-form" style="margin-top: 0;" wire:submit="actualizarPerfil">
            <label>
                Nombre completo
                <input
                    type="text"
                    wire:model="nombre"
                    maxlength="120"
                    autocomplete="name"
                    required
                >
                @error('nombre') <span class="field-error" role="alert">{{ $message }}</span> @enderror
            </label>

            <label>
                Correo electrónico de acceso
                <input
                    type="email"
                    wire:model="correo"
                    maxlength="150"
                    autocomplete="email"
                    required
                >
                @error('correo') <span class="field-error" role="alert">{{ $message }}</span> @enderror
            </label>

            <label>
                Contraseña actual (requerida si modificas tu correo electrónico)
                <input
                    type="password"
                    wire:model="password_actual"
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña actual para confirmar cambios sensibles"
                >
                @error('password_actual') <span class="field-error" role="alert">{{ $message }}</span> @enderror
            </label>

            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Guardar cambios del perfil</span>
                <span wire:loading>Guardando...</span>
            </button>
        </form>
    </div>
</section>
