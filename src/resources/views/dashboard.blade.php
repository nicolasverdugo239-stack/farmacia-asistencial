<x-layouts.app titulo="Inicio">
    <div class="flex flex-col gap-1.5">
        <h1 class="titulo">Hola, {{ explode(' ', auth()->user()->name)[0] }}</h1>
        <p class="text-sm text-secundario">Resumen del turno</p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Descuadres en controlados', $descuadres, 'text-peligro'],
            ['Stock bajo el mínimo', $stockCritico, 'text-aviso'],
            ['Lotes por vencer (60 días)', $porVencer, 'text-tinta'],
            ['Dispensaciones hoy', $dispensacionesHoy, 'text-primario'],
        ] as [$texto, $valor, $color])
            <div class="card flex flex-col gap-1.5 p-4">
                <span class="text-[13px] font-semibold text-secundario">{{ $texto }}</span>
                <span class="text-3xl font-bold {{ $color }}">{{ $valor }}</span>
            </div>
        @endforeach
    </div>

    <section class="card flex flex-col gap-2.5 p-5" aria-labelledby="alertas-t">
        <h2 id="alertas-t" class="font-serif text-lg font-bold">Alertas activas</h2>
        @forelse ($alertas as $alerta)
            @php
                $estilo = match ($alerta->tipo) {
                    'descuadre' => ['bg-peligro-suave', 'text-peligro', 'Descuadre'],
                    'stock_critico', 'carro' => ['bg-aviso-suave', 'text-aviso', 'Stock'],
                    default => ['bg-fondo', 'text-[#3E5057]', 'Vencimiento'],
                };
            @endphp
            <div class="flex flex-wrap items-center gap-3 rounded-lg p-3 {{ $estilo[0] }}">
                <span class="chip min-w-24 bg-white text-center {{ $estilo[1] }}">{{ $estilo[2] }}</span>
                <span class="flex-1 text-sm">{{ $alerta->mensaje }}</span>
                <form method="POST" action="{{ route('alertas.leida', $alerta) }}">
                    @csrf
                    <button class="btn-g btn-s">Marcar revisada</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-secundario">No hay alertas pendientes.</p>
        @endforelse
    </section>
</x-layouts.app>
