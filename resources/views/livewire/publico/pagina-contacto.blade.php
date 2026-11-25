<div>
    <main id="contenido">
        <section class="page-banner" aria-labelledby="contacto-hero-title">
            <div class="container">
                <p class="eyebrow">{{ $contacto->nombre_empresa }}</p>
                <h1 id="contacto-hero-title">Contáctanos</h1>
                <p>Estamos listos para asesorarte en la compra, venta, alquiler o inversión de tu propiedad.</p>
            </div>
        </section>

        <section class="section" aria-labelledby="canales-contacto-title">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow eyebrow--blue">Atención directa</p>
                    <h2 id="canales-contacto-title">Medios de contacto oficiales</h2>
                </div>

                <div class="contact-channels">
                    <article class="card info-card">
                        <h3>Teléfono y WhatsApp</h3>
                        @if($contacto->telefono_visible)
                            <p>
                                <strong>Teléfono:</strong>
                                <a href="tel:{{ $contacto->telefono_enlace }}">{{ $contacto->telefono_visible }}</a>
                            </p>
                        @else
                            <p><strong>Teléfono:</strong> Pendiente de configuración</p>
                        @endif

                        @if($contacto->whatsapp_general)
                            <p>
                                <strong>WhatsApp:</strong>
                                <a href="https://wa.me/{{ $contacto->whatsapp_general }}" target="_blank" rel="noopener noreferrer">
                                    Enviar mensaje por WhatsApp
                                </a>
                            </p>
                        @else
                            <p><strong>WhatsApp:</strong> Pendiente de configuración</p>
                        @endif
                    </article>

                    <article class="card info-card">
                        <h3>Correo electrónico</h3>
                        @if($contacto->correo_contacto)
                            <p>
                                <a href="mailto:{{ $contacto->correo_contacto }}">{{ $contacto->correo_contacto }}</a>
                            </p>
                        @else
                            <p>Pendiente de configuración</p>
                        @endif
                    </article>

                    <article class="card info-card">
                        <h3>Dirección y cobertura</h3>
                        <p>{{ $contacto->direccion ?: 'Pendiente de configuración' }}</p>
                    </article>

                    <article class="card info-card">
                        <h3>Horario de atención</h3>
                        <p>{{ $contacto->horario_lunes_viernes ?: 'Pendiente de configuración' }}</p>
                        <p>{{ $contacto->horario_sabados ?: 'Pendiente de configuración' }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="cta" aria-labelledby="contacto-cta-title">
            <div class="container">
                <h2 id="contacto-cta-title">Trabaja con nosotros</h2>
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
