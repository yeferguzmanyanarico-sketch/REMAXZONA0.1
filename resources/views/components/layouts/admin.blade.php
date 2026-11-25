<!doctype html>
@php
    $temaActivo = auth()->check()
        ? (auth()->user()->tema ?? session('admin_tema', 'claro'))
        : session('admin_tema', 'claro');
@endphp
<html lang="es" class="{{ $temaActivo === 'oscuro' ? 'dark-mode' : '' }}" data-theme="{{ $temaActivo }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Panel Administrativo | RE/MAX Zona' }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    @livewireStyles
</head>
<body class="{{ $temaActivo === 'oscuro' ? 'dark-mode' : '' }}">
    @auth
        <a class="skip-link" href="#contenido-admin">Saltar al contenido principal</a>
        <section class="dashboard">
            <aside class="sidebar" aria-label="Menú lateral de administración">
                <a class="sidebar-brand" href="{{ route('inicio') }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ asset('images/CONTENEDOR.png') }}" alt="RE/MAX Zona">
                    <span>Zona Inmobiliaria</span>
                </a>

                <nav class="sidebar-nav" aria-label="Secciones de administración">
                    <a href="{{ route('inicio') }}" target="_blank" rel="noopener noreferrer">
                        <span aria-hidden="true">⌂</span> Ver sitio público
                    </a>

                    <a href="{{ route('admin.propiedades') }}" class="{{ request()->routeIs('admin.propiedades') ? 'is-active' : '' }}" @if(request()->routeIs('admin.propiedades')) aria-current="page" @endif>
                        <span aria-hidden="true">▦</span> Propiedades
                    </a>

                    <a href="{{ route('admin.agentes') }}" class="{{ request()->routeIs('admin.agentes') ? 'is-active' : '' }}" @if(request()->routeIs('admin.agentes')) aria-current="page" @endif>
                        <span aria-hidden="true">👥</span> Agentes
                    </a>

                    <a href="{{ route('admin.ranking.actual') }}" class="{{ request()->routeIs('admin.ranking.actual') ? 'is-active' : '' }}" @if(request()->routeIs('admin.ranking.actual')) aria-current="page" @endif>
                        <span aria-hidden="true">★</span> Ranking Mensual
                    </a>

                    @if(auth()->user()->esAdministrador())
                        <a href="{{ route('admin.portada') }}" class="{{ request()->routeIs('admin.portada') ? 'is-active' : '' }}" @if(request()->routeIs('admin.portada')) aria-current="page" @endif>
                            <span aria-hidden="true">🖼</span> Portada principal
                        </a>

                        <a href="{{ route('admin.oficinas') }}" class="{{ request()->routeIs('admin.oficinas') ? 'is-active' : '' }}" @if(request()->routeIs('admin.oficinas')) aria-current="page" @endif>
                            <span aria-hidden="true">🏢</span> Oficinas
                        </a>

                        <a href="{{ route('admin.ranking.historial') }}" class="{{ request()->routeIs('admin.ranking.historial') ? 'is-active' : '' }}" @if(request()->routeIs('admin.ranking.historial')) aria-current="page" @endif>
                            <span aria-hidden="true">⌚</span> Historial de Rankings
                        </a>

                        <a href="{{ route('admin.contacto') }}" class="{{ request()->routeIs('admin.contacto') ? 'is-active' : '' }}" @if(request()->routeIs('admin.contacto')) aria-current="page" @endif>
                            <span aria-hidden="true">☎</span> Contacto Global
                        </a>

                        <a href="{{ route('admin.usuarios') }}" class="{{ request()->routeIs('admin.usuarios') ? 'is-active' : '' }}" @if(request()->routeIs('admin.usuarios')) aria-current="page" @endif>
                            <span aria-hidden="true">⚙</span> Usuarios y Roles
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit', 'appearance.edit', 'security.edit', 'admin.configuracion.*') ? 'is-active' : '' }}" @if(request()->routeIs('profile.edit', 'appearance.edit', 'security.edit', 'admin.configuracion.*')) aria-current="page" @endif>
                        <span aria-hidden="true">👤</span> Mi Cuenta y Seguridad
                    </a>
                </nav>

                <div class="sidebar-footer">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="sign-out" type="submit">
                            <span aria-hidden="true">↪</span> Cerrar sesión
                        </button>
                    </form>
                </div>
            </aside>

            <div class="dashboard-content">
                <header class="topbar">
                    <div>
                        <strong>Panel de Administración</strong>
                    </div>
                    <div class="topbar-user">
                        <a href="{{ route('appearance.edit') }}" class="theme-badge-link" title="Cambiar apariencia ({{ $temaActivo === 'oscuro' ? 'Modo oscuro' : 'Modo claro' }})">
                            {{ $temaActivo === 'oscuro' ? '🌙 Oscuro' : '☀ Claro' }}
                        </a>
                        <span class="role-badge">{{ auth()->user()->rol }}</span>
                        <a href="{{ route('profile.edit') }}" class="user-profile-link" title="Configurar mi cuenta">
                            <span class="user-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}</span>
                            <span>{{ auth()->user()->nombre }}</span>
                        </a>
                    </div>
                </header>

                <main class="workspace" id="contenido-admin" tabindex="-1">
                    {{ $slot }}
                </main>
            </div>
        </section>
    @else
        {{ $slot }}
    @endauth

    @livewireScripts
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('tema-actualizado', (event) => {
                const nuevoTema = event.tema || (Array.isArray(event) && event[0]?.tema) || 'claro';
                const esOscuro = nuevoTema === 'oscuro';
                document.documentElement.classList.toggle('dark-mode', esOscuro);
                document.documentElement.setAttribute('data-theme', nuevoTema);
                document.body.classList.toggle('dark-mode', esOscuro);
            });
        });
    </script>
</body>
</html>
