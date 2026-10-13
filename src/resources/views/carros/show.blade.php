<x-layouts.app titulo="Inventario / Carro de emergencia">
    <div class="flex flex-col gap-1.5">
        <h1 class="titulo">Carro {{ $carrito->codigo }} · {{ $carrito->servicio }}</h1>
        <p class="text-sm text-secundario">Un mismo mueble con dos inventarios independientes; cada consumo descuenta solo su lado.</p>
    </div>

    <div class="grid items-start gap-5 lg:grid-cols-2">
        @foreach ($carrito->secciones as $seccion)
            @php($bajos = $seccion->stock->filter->bajoMinimo()->count())
            <section class="card flex flex-col gap-1 p-5">
                <div class="flex items-center justify-between pb-2">
                    <h2 class="font-serif text-lg font-bold">Lado {{ $seccion->nombre() }}</h2>
                    <span class="chip {{ $bajos ? 'bg-aviso-suave text-aviso' : 'bg-exito-suave text-exito' }}">
                        {{ $bajos ? $bajos.' por reponer' : 'Completo' }}
                    </span>
                </div>
                @foreach ($seccion->stock as $item)
                    @php($pct = $item->stock_maximo ? round($item->cantidad / $item->stock_maximo * 100) : 0)
                    <div class="flex flex-col gap-2 border-t border-[#EDF1EF] py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold">{{ $item->producto->nombre }}</span>
                            <span class="text-[13px] {{ $item->bajoMinimo() ? 'font-semibold text-aviso' : 'text-secundario' }}">
                                {{ $item->cantidad }} de {{ $item->stock_maximo }} · mín. {{ $item->stock_minimo }}
                            </span>
                        </div>
                        <progress class="h-1.5 w-full" value="{{ $item->cantidad }}" max="{{ $item->stock_maximo }}" aria-label="Nivel {{ $pct }}%">{{ $pct }}%</progress>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('carros.consumo', $item) }}" class="flex gap-2">
                                @csrf
                                <input type="hidden" name="cantidad" value="1">
                                <button class="btn-g btn-s">Registrar consumo</button>
                            </form>
                            <form method="POST" action="{{ route('carros.reposicion', $item) }}">
                                @csrf
                                <button class="btn-g btn-s" @disabled($item->cantidad >= $item->stock_maximo)>Reponer desde bodega</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach
    </div>
</x-layouts.app>
