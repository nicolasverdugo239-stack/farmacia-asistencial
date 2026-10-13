@props(['titulo' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? 'Farmacia Asistencial' }} · Farmacia Asistencial</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-fondo font-sans text-tinta antialiased">
@php
    $menu = [
        ['ruta' => 'dashboard', 'texto' => 'Inicio', 'permiso' => null],
        ['ruta' => 'dispensaciones.buscar', 'texto' => 'Dispensar receta', 'permiso' => 'dispensar'],
        ['ruta' => 'remesas.create', 'texto' => 'Remesas CENABAST', 'permiso' => 'recibir remesas'],
        ['ruta' => 'carros.index', 'texto' => 'Carro Paro / Unidosis', 'permiso' => 'gestionar carro'],
        ['ruta' => 'controlados.index', 'texto' => 'Libro de controlados', 'permiso' => 'ver libro controlados'],
        ['ruta' => 'usuarios.index', 'texto' => 'Usuarios y roles', 'permiso' => 'gestionar usuarios'],
        ['ruta' => 'auditoria.index', 'texto' => 'Auditoría', 'permiso' => 'ver auditoria'],
    ];
    $rol = ['administrador' => 'Administrador', 'tecnico' => 'Técnico farmacéutico', 'quimico' => 'Químico farmacéutico'][auth()->user()->getRoleNames()->first()] ?? 'Sin rol';
@endphp
<div class="flex min-h-screen flex-col lg:flex-row">
    {{-- Escritorio: menú lateral fijo. Móvil/tablet: menú desplegable arriba. --}}
    <nav aria-label="Menú principal" class="border-b border-borde bg-white lg:w-62 lg:shrink-0 lg:border-r lg:border-b-0">
        <details class="group" open>
            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between px-4 lg:pointer-events-none lg:py-5">
                <span class="flex items-center gap-2.5 font-bold text-primario">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/></svg>
                    Farmacia Asistencial
                </span>
                <span class="text-sm text-secundario lg:hidden">Menú</span>
            </summary>
            <div class="flex flex-col gap-1 px-3 pb-4">
                @foreach ($menu as $item)
                    @if (is_null($item['permiso']) || auth()->user()->can($item['permiso']))
                        <a href="{{ route($item['ruta']) }}"
                           @class([
                               'rounded-lg px-4 py-3 text-sm',
                               'bg-primario-suave font-bold text-primario' => request()->routeIs(explode('.', $item['ruta'])[0].'*'),
                               'font-medium text-[#3E5057] hover:bg-fondo' => ! request()->routeIs(explode('.', $item['ruta'])[0].'*'),
                           ])>{{ $item['texto'] }}</a>
                    @endif
                @endforeach
            </div>
        </details>
    </nav>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-borde bg-white px-4 md:px-7">
            <span class="text-sm text-secundario">{{ $titulo ?? '' }}</span>
            <div class="flex items-center gap-3">
                <span class="chip bg-primario-suave text-primario">{{ $rol }}</span>
                <span class="hidden text-sm font-semibold sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-g btn-s">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <main class="flex flex-1 flex-col gap-5 p-4 md:p-7">
            @include('partials.flash')
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
