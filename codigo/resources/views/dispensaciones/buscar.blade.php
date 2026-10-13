<x-layouts.app titulo="Dispensación">
    <div class="flex flex-col gap-1.5">
        <h1 class="titulo">Dispensar receta</h1>
        <p class="text-sm text-secundario">Busca por folio de la receta o por RUT del paciente.</p>
    </div>

    <form method="GET" action="{{ route('dispensaciones.buscar') }}" class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-end">
        <label class="lbl flex-1">Folio o RUT
            <input class="inp" name="q" value="{{ $termino }}" maxlength="20" placeholder="Ej: 10452 o 12.345.678-5" required>
        </label>
        <button class="btn-p">Buscar receta</button>
    </form>

    @if ($termino !== '')
        @if ($recetas->isEmpty())
            <p class="text-sm text-secundario">No se encontraron recetas vigentes para «{{ $termino }}».</p>
        @else
            <div class="card divide-y divide-[#EDF1EF]">
                @foreach ($recetas as $receta)
                    <a href="{{ route('dispensaciones.create', $receta) }}" class="flex items-center justify-between gap-3 p-4 hover:bg-fondo">
                        <span><strong>Receta {{ $receta->folio }}</strong> · {{ $receta->paciente->nombre }}</span>
                        <span class="text-sm text-secundario">Emitida {{ $receta->fecha_emision->format('d-m-Y') }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</x-layouts.app>
