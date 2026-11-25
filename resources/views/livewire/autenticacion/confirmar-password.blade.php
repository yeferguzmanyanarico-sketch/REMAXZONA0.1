<section>
    <div class="card-box" style="max-width: 520px; margin: 2.5rem auto;">
        <p class="eyebrow">Área protegida</p>
        <h1 style="font-size: 1.55rem; margin-bottom: .5rem;">Confirmar contraseña</h1>
        <p style="color: var(--muted);">
            Por tu seguridad, confirma tu contraseña actual antes de acceder a esta sección sensible.
        </p>

        <form class="content-form" wire:submit="confirmar">
            <label>
                Contraseña actual
                <input
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    required
                >
                @error('password')
                    <span class="field-error" role="alert">{{ $message }}</span>
                @enderror
            </label>

            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Confirmar contraseña</span>
                <span wire:loading>Verificando...</span>
            </button>
        </form>
    </div>
</section>
