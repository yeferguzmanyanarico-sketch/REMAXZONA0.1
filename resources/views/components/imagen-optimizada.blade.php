@props([
    'ruta' => '',
    'src' => '',
    'alt' => '',
    'width' => null,
    'height' => null,
    'lazy' => true,
    'fetchpriority' => null,
    'class' => '',
])
@php
    $urlWebp = $ruta !== '' ? app(\App\Services\ServicioOptimizacionImagen::class)->obtenerUrlWebp($ruta) : null;
@endphp
<picture>
    @if($urlWebp)
        <source srcset="{{ $urlWebp }}" type="image/webp">
    @endif
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        @if($class !== '') class="{{ $class }}" @endif
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        @if($lazy) loading="lazy" @endif
        @if($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
        decoding="async"
    >
</picture>
