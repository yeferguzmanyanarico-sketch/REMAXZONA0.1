<div>
    <main id="contenido">
        <section class="page-banner" aria-labelledby="empresa-hero-title">
            <div class="container">
                <p class="eyebrow">{{ $contacto->nombre_empresa }}</p>
                <h1 id="empresa-hero-title">Sobre nosotros</h1>
                <p>Inmobiliaria especializada en compra, venta, alquiler e inversión inmobiliaria en el sur del Perú.</p>
            </div>
        </section>

        <section class="section" id="nosotros" aria-labelledby="about-title">
            <div class="container about-grid">
                <div class="about-image">
                    <x-imagen-optimizada
                        ruta="images/Premios.jpeg"
                        :src="asset('images/Premios.jpeg')"
                        alt="Reconocimientos recibidos por RE/MAX Zona"
                        width="800"
                        height="600"
                    />
                </div>
                <div class="about-copy">
                    <p class="eyebrow eyebrow--blue">{{ $contacto->nombre_empresa }}</p>
                    <h2 id="about-title">Quiénes somos</h2>
                    <p>Somos una empresa inmobiliaria líder en el sur del país, comprometida con transformar la experiencia inmobiliaria mediante un servicio profesional, transparente y orientado a resultados.</p>
                    <p>Nuestra meta es consolidarnos como el referente de la red en la región sur y en todo el Perú.</p>
                    @if($contacto->direccion)
                        <p class="about-meta"><strong>Cobertura:</strong> {{ $contacto->direccion }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="identity" aria-labelledby="identity-title">
            <div class="container">
                <div class="section-heading section-heading--light">
                    <p class="eyebrow">Nuestro propósito</p>
                    <h2 id="identity-title">Identidad corporativa</h2>
                </div>
                <div class="identity-grid">
                    <article>
                        <h3>Misión</h3>
                        <p>Ser la oficina más importante de la red en la región y referente inmobiliario en el sur del Perú.</p>
                    </article>
                    <article>
                        <h3>Visión</h3>
                        <p>Extendernos en toda la región sur como oficinas independientes bajo la marca RE/MAX Zona.</p>
                    </article>
                    <article>
                        <h3>Valores</h3>
                        <ul>
                            <li>Transparencia</li>
                            <li>Confianza</li>
                            <li>Ética profesional</li>
                            <li>Compromiso con el cliente</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" aria-labelledby="tops-title">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow eyebrow--blue">Nuestro equipo</p>
                    <h2 id="tops-title">Nuestros destacados</h2>
                    @if($rankingPublicado)
                        <p class="section-subtitle">
                            Vendedores estrella — Desempeño de {{ $rankingPublicado->etiqueta_desempeno }}
                        </p>
                    @endif
                </div>

                @if($rankingPublicado && $rankingPublicado->posiciones->count() === 3)
                    <div class="card-grid card-grid--agents" id="featured-agents">
                        @foreach($rankingPublicado->posiciones as $posicion)
                            <article class="card agent-card">
                                <span class="agent-badge agent-badge--{{ $posicion->puesto }}">
                                    @if($posicion->puesto === 1)
                                        1.er Puesto
                                    @elseif($posicion->puesto === 2)
                                        2.º Puesto
                                    @else
                                        3.er Puesto
                                    @endif
                                </span>
                                <x-imagen-optimizada
                                    :ruta="$posicion->foto_snapshot ?? ''"
                                    :src="$posicion->url_foto_snapshot"
                                    alt="{{ $posicion->nombre_completo_snapshot }}, asesor inmobiliario"
                                    width="400"
                                    height="480"
                                />
                                <h3>{{ $posicion->nombre_completo_snapshot }}</h3>
                                <a
                                    href="https://api.whatsapp.com/send/?phone={{ $posicion->celular_whatsapp_snapshot }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    WhatsApp: {{ $posicion->celular_snapshot }}
                                </a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="ranking-updating-box" role="status">
                        Ranking mensual en actualización. Pronto conocerás a nuestros vendedores destacados del mes
                    </div>
                @endif
            </div>
        </section>

        <section class="section section--muted" id="directorio-agentes" aria-labelledby="directory-agents-title">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow eyebrow--blue">Directorio institucional</p>
                    <h2 id="directory-agents-title">Nuestros agentes inmobiliarios</h2>
                    <p class="section-subtitle">
                        Conoce a los profesionales autorizados de {{ $contacto->nombre_empresa }} listos para brindarte asesoría personalizada.
                    </p>
                </div>

                @if($agentesActivos->isNotEmpty())
                    <div class="card-grid card-grid--agents" id="company-agents-directory">
                        @foreach($agentesActivos as $agente)
                            <article class="card directory-agent-card" wire:key="agente-publico-{{ $agente->id }}">
                                <x-imagen-optimizada
                                    class="directory-agent-card__photo"
                                    :ruta="$agente->fotografia ?? ''"
                                    :src="$agente->url_fotografia"
                                    alt="{{ $agente->nombre_completo }}, agente inmobiliario de {{ $contacto->nombre_empresa }}"
                                    width="400"
                                    height="480"
                                />
                                <div class="directory-agent-card__body">
                                    <h3>{{ $agente->nombre_completo }}</h3>
                                    <p class="directory-agent-card__bio">{{ $agente->biografia }}</p>

                                    <div class="directory-agent-card__contacts">
                                        @if($agente->celular)
                                            <a href="{{ $agente->celular_enlace }}" class="directory-agent-contact">
                                                <span class="directory-agent-contact__icon" aria-hidden="true">☎</span>
                                                <span class="directory-agent-contact__text">Celular: {{ $agente->celular }}</span>
                                            </a>
                                        @endif

                                        @if($agente->whatsapp)
                                            <a
                                                href="{{ $agente->whatsapp_enlace }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="directory-agent-contact directory-agent-contact--whatsapp"
                                            >
                                                <span class="directory-agent-contact__icon" aria-hidden="true">💬</span>
                                                <span class="directory-agent-contact__text">WhatsApp: {{ $agente->whatsapp_visible }}</span>
                                            </a>
                                        @endif

                                        @if($agente->correo)
                                            <a href="mailto:{{ $agente->correo }}" class="directory-agent-contact">
                                                <span class="directory-agent-contact__icon" aria-hidden="true">✉</span>
                                                <span class="directory-agent-contact__text">{{ $agente->correo }}</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state-box" role="status">
                        <p>Pronto publicaremos el directorio actualizado de nuestros agentes inmobiliarios.</p>
                    </div>
                @endif
            </div>
        </section>

        <section class="cta" aria-labelledby="cta-title">
            <div class="container">
                <h2 id="cta-title">Trabaja con nosotros</h2>
                <p>Desarrolla tu carrera con la red RE/MAX y alcanza tu máximo potencial profesional.</p>
                <a
                    class="button button--accent"
                    href="https://api.whatsapp.com/send/?phone={{ $contacto->whatsapp_postulacion }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Postular por WhatsApp
                </a>
            </div>
        </section>
    </main>
</div>
