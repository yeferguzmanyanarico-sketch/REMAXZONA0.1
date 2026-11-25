<!doctype html>
<html lang="es">
<head>
    @php
        $contacto = \App\Models\ConfiguracionContacto::obtenerUnica();
        $seoTitulo = $title ?? ($contacto->nombre_empresa . ' | Inmobiliaria');
        $seoDescripcion = $metaDescription ?? 'RE/MAX Zona: compra, venta, alquiler e inversión de propiedades en el sur del Perú.';
        $seoCanonical = $canonicalUrl ?? url()->current();
        $seoImagen = $ogImage ?? asset('images/CONTENEDOR.png');
        $seoTipo = $ogType ?? 'website';

        $esquemaOrganizacion = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateAgent',
            'name' => $contacto->nombre_empresa,
            'url' => url('/'),
            'logo' => asset('images/CONTENEDOR.png'),
        ];
        if (! empty($contacto->telefono_visible)) {
            $esquemaOrganizacion['telephone'] = $contacto->telefono_visible;
        }
        if (! empty($contacto->correo_contacto)) {
            $esquemaOrganizacion['email'] = $contacto->correo_contacto;
        }
        if (! empty($contacto->direccion)) {
            $esquemaOrganizacion['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $contacto->direccion,
                'addressCountry' => 'PE',
            ];
        }
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $seoDescripcion }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:locale" content="es_PE">
    <meta property="og:type" content="{{ $seoTipo }}">
    <meta property="og:site_name" content="{{ $contacto->nombre_empresa }}">
    <meta property="og:title" content="{{ $seoTitulo }}">
    <meta property="og:description" content="{{ $seoDescripcion }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImagen }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitulo }}">
    <meta name="twitter:description" content="{{ $seoDescripcion }}">
    <meta name="twitter:image" content="{{ $seoImagen }}">

    <title>{{ $seoTitulo }}</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}?v={{ filemtime(public_path('css/styles.css')) }}">
    <script type="application/ld+json">{!! json_encode($esquemaOrganizacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @if(! empty($datosEstructurados))
        <script type="application/ld+json">{!! json_encode($datosEstructurados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
    @livewireStyles
</head>
<body>

    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="site-header">
        <a class="brand" href="{{ route('inicio') }}" aria-label="RE/MAX Zona, ir al inicio">
            <x-imagen-optimizada
                ruta="images/CONTENEDOR.png"
                :src="asset('images/CONTENEDOR.png')"
                alt="RE/MAX Zona"
                width="180"
                height="60"
                :lazy="false"
            />
        </a>
        <nav class="site-nav" aria-label="Navegación principal">
            <a href="{{ route('inicio') }}" @class(['is-active' => request()->routeIs('inicio')]) @if(request()->routeIs('inicio')) aria-current="page" @endif>Inicio</a>
            <a href="{{ route('empresa') }}" @class(['is-active' => request()->routeIs('empresa')]) @if(request()->routeIs('empresa')) aria-current="page" @endif>Empresa</a>
            <a href="{{ route('propiedades') }}" @class(['is-active' => request()->routeIs('propiedades')]) @if(request()->routeIs('propiedades')) aria-current="page" @endif>Propiedades</a>
            <a href="{{ route('oficinas') }}" @class(['is-active' => request()->routeIs('oficinas', 'oficinas.*')]) @if(request()->routeIs('oficinas', 'oficinas.*')) aria-current="page" @endif>Oficinas</a>
            <a href="{{ route('contacto') }}" @class(['is-active' => request()->routeIs('contacto')]) @if(request()->routeIs('contacto')) aria-current="page" @endif>Contacto</a>
        </nav>
    </header>

    {{ $slot }}

    <footer class="site-footer" id="contacto">
        <div class="container footer-grid">
            <div>
                <h2>{{ $contacto->nombre_empresa }}</h2>
                <p>Inmobiliaria especializada en compra, venta, alquiler e inversión inmobiliaria.</p>
                @if($contacto->direccion)
                    <p>{{ $contacto->direccion }}</p>
                @endif
            </div>
            <div>
                <h3>Contacto</h3>
                <p><a href="tel:{{ $contacto->telefono_enlace }}">{{ $contacto->telefono_visible }}</a></p>
                <p><a href="mailto:{{ $contacto->correo_contacto }}">{{ $contacto->correo_contacto }}</a></p>
                <p><a href="https://wa.me/{{ $contacto->whatsapp_general }}" target="_blank" rel="noopener noreferrer">WhatsApp directo</a></p>
            </div>
            <div>
                <h3>Horario</h3>
                <p>{{ $contacto->horario_lunes_viernes }}</p>
                <p>{{ $contacto->horario_sabados }}</p>
            </div>
        </div>
        <div class="footer-bottom">© {{ now('America/Lima')->year }} {{ $contacto->nombre_empresa }}. Todos los derechos reservados.</div>
    </footer>

    <a
        class="whatsapp"
        href="https://wa.me/{{ $contacto->whatsapp_general }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Contactar por WhatsApp"
    >
        💬<span> WhatsApp</span>
    </a>

    @livewireScripts
</body>
</html>
