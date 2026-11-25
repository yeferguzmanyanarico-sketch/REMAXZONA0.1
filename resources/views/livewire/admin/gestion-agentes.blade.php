<section>
    @if($mensajeExito)
        <p class="message success" role="status">{{ $mensajeExito }}</p>
    @endif
    @if($mensajeError)
        <p class="message" role="alert">{{ $mensajeError }}</p>
    @endif

    <div class="page-heading">
        <div>
            <p class="eyebrow">Directorio institucional</p>
            <h1><span aria-hidden="true">👥</span> Gestión de agentes</h1>
            <p>Registra, edita, activa o inactiva a los agentes inmobiliarios que se muestran en la página pública Empresa.</p>
        </div>
        <button type="button" wire:click="nuevoAgente">＋ Registrar agente</button>
    </div>

    <div class="management-layout">
        <div class="data-area">
            <div class="list-toolbar">
                <input
                    type="search"
                    class="search-input"
                    wire:model.live.debounce.250ms="busqueda"
                    placeholder="Buscar por nombres, apellidos, celular o correo..."
                >
                <span class="count-label">
                    {{ $agentes->count() }} {{ $agentes->count() === 1 ? 'agente' : 'agentes' }}
                </span>
            </div>

            <div class="list-head list-head--directory-agents">
                <span>Foto</span>
                <span>Agente y biografía</span>
                <span>Contacto</span>
                <span>Estado</span>
                <span>Acciones</span>
            </div>

            <div class="content-list">
                @forelse($agentes as $agente)
                    <article class="item directory-agent-item" wire:key="agente-dir-{{ $agente->id }}">
                        <img src="{{ $agente->url_fotografia }}" alt="{{ $agente->nombre_completo }}">
                        <div>
                            <h3>{{ $agente->nombre_completo }}</h3>
                            <p class="agent-bio-excerpt">{{ \Illuminate\Support\Str::limit($agente->biografia, 95) }}</p>
                        </div>
                        <div>
                            <p>☎ Cel: {{ $agente->celular }}</p>
                            <p>💬 WA: {{ $agente->whatsapp_visible }}</p>
                            @if($agente->correo)
                                <p>✉ {{ $agente->correo }}</p>
                            @endif
                        </div>
                        <div>
                            <span class="status {{ $agente->activo ? '' : 'off' }}">
                                {{ $agente->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <div class="item-actions">
                            <button
                                type="button"
                                class="state-toggle {{ $agente->activo ? 'is-active-state' : '' }}"
                                title="{{ $agente->activo ? 'Inactivar agente' : 'Activar agente' }}"
                                aria-label="{{ $agente->activo ? 'Inactivar agente' : 'Activar agente' }}"
                                wire:click="alternarEstado({{ $agente->id }})"
                            >{{ $agente->activo ? '✓' : '○' }}</button>
                            <button
                                type="button"
                                title="Editar agente"
                                aria-label="Editar agente {{ $agente->nombre_completo }}"
                                wire:click="editarAgente({{ $agente->id }})"
                            >✎</button>
                            <button
                                type="button"
                                class="delete"
                                title="Eliminar agente"
                                aria-label="Eliminar agente {{ $agente->nombre_completo }}"
                                wire:click="eliminarAgente({{ $agente->id }})"
                                wire:confirm="¿Deseas eliminar al agente «{{ $agente->nombre_completo }}» del directorio?"
                            >⌫</button>
                        </div>
                    </article>
                @empty
                    <div style="padding: 1.5rem; text-align: center; color: var(--muted);">
                        No se encontraron agentes registrados en el directorio.
                    </div>
                @endforelse
            </div>
        </div>

        @if($mostrarFormulario)
            <form class="content-form editor-panel" wire:submit="guardarAgente">
                <div class="form-header">
                    <h2>{{ $agenteId ? 'Editar agente' : 'Registrar agente' }}</h2>
                    <button class="icon-button" type="button" wire:click="limpiarFormulario" aria-label="Cerrar formulario">×</button>
                </div>

                <label>
                    Nombres
                    <input type="text" wire:model="nombres" maxlength="100" required>
                    @error('nombres') <span class="field-error">{{ $message }}</span> @enderror
                </label>

                <label>
                    Apellidos
                    <input type="text" wire:model="apellidos" maxlength="100" required>
                    @error('apellidos') <span class="field-error">{{ $message }}</span> @enderror
                </label>

                <label>
                    Biografía profesional
                    <textarea
                        rows="4"
                        wire:model="biografia"
                        maxlength="1000"
                        placeholder="Describe la experiencia y especialidad del agente..."
                        required
                    ></textarea>
                    @error('biografia') <span class="field-error">{{ $message }}</span> @enderror
                </label>

                <div class="price-grid" style="grid-template-columns: 1fr 1fr;">
                    <label>
                        Celular
                        <div class="phone-input-group">
                            <span class="phone-prefix" aria-hidden="true">+51</span>
                            <input type="tel" wire:model="celular" maxlength="25" placeholder="Ej. 999 999 999" required>
                        </div>
                        @error('celular') <span class="field-error">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        WhatsApp
                        <div class="phone-input-group">
                            <span class="phone-prefix" aria-hidden="true">+51</span>
                            <input type="tel" wire:model="whatsapp" maxlength="25" placeholder="Ej. 999 999 999" required>
                        </div>
                        @error('whatsapp') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                </div>

                <label>
                    Correo electrónico (opcional)
                    <input type="email" wire:model="correo" maxlength="150" placeholder="agente@remaxzona.com">
                    @error('correo') <span class="field-error">{{ $message }}</span> @enderror
                </label>

                <div class="office-image-preview-box">
                    <span class="readonly-meta-label">
                        Fotografía {{ $agenteId ? '(opcional al editar)' : '' }}
                    </span>

                    @if($fotografiaArchivo && method_exists($fotografiaArchivo, 'isPreviewable') && $fotografiaArchivo->isPreviewable())
                        <img
                            class="office-preview-img"
                            src="{{ $fotografiaArchivo->temporaryUrl() }}"
                            alt="Vista previa de nueva fotografía para {{ trim($nombres . ' ' . $apellidos) ?: 'el agente' }}"
                        >
                        <div class="preview-caption-row">
                            <small class="preview-caption">Nueva fotografía seleccionada (sin guardar aún)</small>
                            <button type="button" class="preview-clear-btn" wire:click="limpiarFotografiaSeleccionada">
                                Quitar selección
                            </button>
                        </div>
                    @elseif($urlFotografiaActual)
                        <img
                            class="office-preview-img"
                            src="{{ $urlFotografiaActual }}"
                            alt="Fotografía actual de {{ trim($nombres . ' ' . $apellidos) }}"
                        >
                        <small class="preview-caption">Fotografía actual del agente</small>
                    @endif

                    <div
                        class="agent-photo-dropzone"
                        tabindex="0"
                        role="button"
                        aria-label="Seleccionar fotografía. JPG, PNG o WebP. También puedes arrastrar o pegar una imagen."
                        x-data="{
                            arrastrando: false,
                            asignarArchivo(archivo) {
                                if (! archivo || ! archivo.type || ! archivo.type.startsWith('image/')) {
                                    return;
                                }
                                const transferencia = new DataTransfer();
                                transferencia.items.add(archivo);
                                const entrada = this.$refs.archivoInput;
                                entrada.files = transferencia.files;
                                entrada.dispatchEvent(new Event('change', { bubbles: true }));
                            },
                            manejarSoltar(evento) {
                                this.arrastrando = false;
                                const archivo = evento.dataTransfer?.files?.[0];
                                if (archivo) {
                                    this.asignarArchivo(archivo);
                                }
                            },
                            manejarPegar(evento) {
                                const activo = document.activeElement;
                                const esCampoTexto = activo && (
                                    (activo.tagName === 'INPUT' && activo.type !== 'file')
                                    || activo.tagName === 'TEXTAREA'
                                );
                                if (esCampoTexto && ! this.$el.contains(activo)) {
                                    return;
                                }
                                const items = evento.clipboardData?.items || [];
                                for (const item of items) {
                                    if (item.type && item.type.startsWith('image/')) {
                                        const archivo = item.getAsFile();
                                        if (archivo) {
                                            evento.preventDefault();
                                            this.asignarArchivo(archivo);
                                        }
                                        break;
                                    }
                                }
                            }
                        }"
                        x-on:click="$refs.archivoInput.click()"
                        x-on:keydown.enter.prevent="$refs.archivoInput.click()"
                        x-on:keydown.space.prevent="$refs.archivoInput.click()"
                        x-on:dragover.prevent="arrastrando = true"
                        x-on:dragleave.prevent="arrastrando = false"
                        x-on:drop.prevent="manejarSoltar($event)"
                        x-on:paste.window="manejarPegar($event)"
                        :class="{ 'is-dragover': arrastrando }"
                    >
                        <input
                            x-ref="archivoInput"
                            type="file"
                            class="sr-only"
                            wire:model="fotografiaArchivo"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <svg class="agent-photo-dropzone__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2.5"></rect>
                            <circle cx="8.5" cy="9.5" r="1.5"></circle>
                            <path d="M21 15l-4.5-4.5a1.5 1.5 0 0 0-2.12 0L6 19"></path>
                        </svg>

                        <strong class="agent-photo-dropzone__title">Seleccionar fotografía</strong>
                        <span class="agent-photo-dropzone__formats">JPG, PNG o WebP</span>
                        <small class="agent-photo-dropzone__hint">También puedes arrastrar o pegar una imagen</small>
                    </div>

                    <small class="preview-caption" wire:loading wire:target="fotografiaArchivo">
                        Cargando vista previa...
                    </small>

                    @error('fotografiaArchivo') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                </div>

                <label style="display: flex; align-items: center; gap: .6rem; cursor: pointer;">
                    <input type="checkbox" wire:model="activo" style="width: auto;">
                    <span>Activo (visible en la página pública Empresa)</span>
                </label>

                <button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $agenteId ? 'Guardar cambios' : 'Registrar agente' }}</span>
                    <span wire:loading>Guardando...</span>
                </button>
            </form>
        @else
            <div class="editor-panel" style="color: var(--muted); text-align: center;">
                <p>Selecciona un agente para editarlo o haz clic en <strong>«＋ Registrar agente»</strong>.</p>
            </div>
        @endif
    </div>
</section>
