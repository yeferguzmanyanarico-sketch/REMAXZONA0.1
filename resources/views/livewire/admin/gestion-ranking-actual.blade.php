<section>
    @if($mensajeExito)
        <p class="message success" role="status">{{ $mensajeExito }}</p>
    @endif
    @if($mensajeError)
        <p class="message" role="alert">{{ $mensajeError }}</p>
    @endif

    <div class="page-heading">
        <div>
            <p class="eyebrow">Vendedores Estrella del Mes</p>
            <h1><span aria-hidden="true">★</span> Gestión del Ranking Mensual</h1>
            <p>
                Período de desempeño evaluado: <strong>{{ $etiquetaDesempeno }}</strong> |
                Mes de publicación pública: <strong>{{ $etiquetaPublicacion }}</strong>
            </p>
        </div>
    </div>

    @if($dentroDePlazo)
        <div class="ranking-banner">
            <strong>Plazo habilitado (Días 1 al 3 de {{ $etiquetaPublicacion }}):</strong>
            Registra manualmente a los participantes de los <strong>tres puestos fijos</strong> para el desempeño de <strong>{{ $etiquetaDesempeno }}</strong>
            hasta el día 3 de {{ $etiquetaPublicacion }} a las 11:59 p. m. (Hora Perú: {{ $ahoraLima->format('d/m/Y H:i') }}).
            <br>
            Progreso del período: <strong>{{ $totalRegistrados }} / 3 puestos completados</strong>
            @if($rankingActual)
                | Estado: <strong>{{ $rankingActual->estado }}</strong>
            @endif
        </div>

        @php
            $etiquetasPuestos = [
                1 => 'Primer puesto (1.er Puesto)',
                2 => 'Segundo puesto (2.º Puesto)',
                3 => 'Tercer puesto (3.er Puesto)',
            ];
        @endphp

        <div class="podium-grid">
            @foreach($etiquetasPuestos as $numeroPuesto => $tituloPuesto)
                @php
                    $datosPuesto = $puestos[$numeroPuesto] ?? [];
                    $campoFoto = 'foto' . $numeroPuesto;
                    $archivoTemporal = $this->{$campoFoto};
                    $estaRegistrado = (bool) ($datosPuesto['registrado'] ?? false);
                @endphp

                <div class="podium-card" wire:key="puesto-ranking-{{ $numeroPuesto }}">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .75rem;">
                        <strong style="font-size: .95rem;">{{ $tituloPuesto }}</strong>
                        <span class="status {{ $estaRegistrado ? '' : 'warn' }}">
                            {{ $estaRegistrado ? 'Registrado' : 'Pendiente' }}
                        </span>
                    </div>

                    @if(! empty($datosPuesto['codigo_interno']))
                        <p class="preview-caption" style="text-align: left; margin-bottom: .75rem;">
                            Código interno: <strong>{{ $datosPuesto['codigo_interno'] }}</strong>
                        </p>
                    @endif

                    @if(! empty($advertenciasHomonimo[$numeroPuesto]))
                        <p class="message" role="status" style="text-align: left; font-size: .84rem; margin-bottom: .85rem;">
                            {{ $advertenciasHomonimo[$numeroPuesto] }}
                        </p>
                    @endif

                    <form wire:submit="guardarPuesto({{ $numeroPuesto }})" class="content-form" style="margin-top: 0; text-align: left;">
                        <label>
                            Nombres *
                            <input
                                type="text"
                                wire:model="puestos.{{ $numeroPuesto }}.nombres"
                                maxlength="100"
                                placeholder="Ingresa los nombres"
                                required
                            >
                            @error("puestos.{$numeroPuesto}.nombres")
                                <span class="field-error" role="alert">{{ $message }}</span>
                            @enderror
                        </label>

                        <label>
                            Apellidos *
                            <input
                                type="text"
                                wire:model="puestos.{{ $numeroPuesto }}.apellidos"
                                maxlength="100"
                                placeholder="Ingresa los apellidos"
                                required
                            >
                            @error("puestos.{$numeroPuesto}.apellidos")
                                <span class="field-error" role="alert">{{ $message }}</span>
                            @enderror
                        </label>

                        <label>
                            Número de celular *
                            <div class="phone-input-group">
                                <span class="phone-prefix" aria-hidden="true">+51</span>
                                <input
                                    type="tel"
                                    wire:model="puestos.{{ $numeroPuesto }}.celular"
                                    maxlength="20"
                                    placeholder="Ej. 951 000 000"
                                    required
                                >
                            </div>
                            @error("puestos.{$numeroPuesto}.celular")
                                <span class="field-error" role="alert">{{ $message }}</span>
                            @enderror
                        </label>

                        <div class="office-image-preview-box">
                            <span class="readonly-meta-label">
                                Fotografía para el ranking {{ $estaRegistrado ? '(opcional al editar)' : '*' }}
                            </span>

                            @if($archivoTemporal && method_exists($archivoTemporal, 'isPreviewable') && $archivoTemporal->isPreviewable())
                                <img
                                    class="office-preview-img"
                                    src="{{ $archivoTemporal->temporaryUrl() }}"
                                    alt="Vista previa del puesto {{ $numeroPuesto }}"
                                >
                                <div class="preview-caption-row">
                                    <small class="preview-caption">Nueva fotografía (sin guardar)</small>
                                    <button type="button" class="preview-clear-btn" wire:click="limpiarFotoPuesto({{ $numeroPuesto }})">
                                        Quitar selección
                                    </button>
                                </div>
                            @elseif(! empty($datosPuesto['url_foto_actual']))
                                <img
                                    class="office-preview-img"
                                    src="{{ $datosPuesto['url_foto_actual'] }}"
                                    alt="Fotografía actual del puesto {{ $numeroPuesto }}"
                                >
                                <small class="preview-caption">Fotografía registrada para este puesto</small>
                            @endif

                            <input
                                type="file"
                                wire:model="{{ $campoFoto }}"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                {{ $estaRegistrado ? '' : 'required' }}
                            >
                            <small class="preview-caption">Formatos permitidos: JPG, PNG o WebP (máx. 4 MB).</small>

                            @error($campoFoto)
                                <span class="field-error" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="guardarPuesto({{ $numeroPuesto }}), {{ $campoFoto }}">
                            <span wire:loading.remove wire:target="guardarPuesto({{ $numeroPuesto }})">
                                {{ $estaRegistrado ? 'Actualizar puesto' : 'Guardar puesto' }}
                            </span>
                            <span wire:loading wire:target="guardarPuesto({{ $numeroPuesto }})">Guardando...</span>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="card-box" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div>
                <strong>Publicación en el sitio público</strong>
                <p style="margin: .25rem 0 0; color: var(--muted); font-size: .9rem;">
                    @if($totalRegistrados === 3)
                        Los tres puestos están completos. Ya puedes publicar o actualizar el ranking en el sitio público.
                    @else
                        Debes registrar los 3 puestos obligatorios antes de publicar el ranking ({{ $totalRegistrados }}/3 completados).
                    @endif
                </p>
            </div>

            <button
                type="button"
                class="btn-primary"
                wire:click="publicarRanking"
                wire:loading.attr="disabled"
                @if($totalRegistrados < 3) disabled aria-disabled="true" style="opacity: .6; cursor: not-allowed;" @endif
            >
                {{ $rankingActual && $rankingActual->estado === 'PUBLICADO' ? 'Actualizar Ranking Publicado' : 'Publicar Ranking en el Sitio Público' }}
            </button>
        </div>
    @else
        <div class="ranking-banner locked">
            <strong>Plazo cerrado (A partir del día 4 de {{ $etiquetaPublicacion }}):</strong>
            El plazo para preparar, publicar o modificar el ranking del desempeño de <strong>{{ $etiquetaDesempeno }}</strong>
            finalizó el día 3 de {{ $etiquetaPublicacion }} a las 11:59 p. m. El período actual se encuentra bloqueado automáticamente.
        </div>
    @endif

    @if($rankingActual && $rankingActual->posiciones->count() === 3)
        <div class="card-box" style="margin-top: 1.5rem;">
            <h2>
                Vista del Ranking — Desempeño {{ $etiquetaDesempeno }}
                <span class="status {{ $rankingActual->estado === 'VENCIDO' ? 'off' : '' }}" style="margin-left: .5rem;">
                    {{ $rankingActual->estado }}
                </span>
            </h2>
            <p style="color: var(--muted);">
                Fotografías y datos conservados de manera independiente para este período.
            </p>

            <div class="podium-grid">
                @foreach($rankingActual->posiciones as $pos)
                    <div class="podium-card">
                        <strong>Puesto #{{ $pos->puesto }}</strong>
                        <img src="{{ $pos->url_foto_snapshot }}" alt="{{ $pos->nombre_completo_snapshot }}">
                        <h3>{{ $pos->nombre_completo_snapshot }}</h3>
                        <p>Celular: {{ $pos->celular_snapshot }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif(! $dentroDePlazo)
        <div class="card-box" style="margin-top: 1.5rem; text-align: center; color: var(--muted);">
            <p>
                El ranking correspondiente al desempeño de <strong>{{ $etiquetaDesempeno }}</strong> no fue publicado durante los días 1 al 3 de {{ $etiquetaPublicacion }}.
                Por regla de negocio, no se permite su publicación fuera de plazo. El próximo período se habilitará el día 1 del siguiente mes.
            </p>
        </div>
    @endif
</section>
