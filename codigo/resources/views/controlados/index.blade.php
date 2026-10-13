<x-layouts.app titulo="Controlados / Libro digital">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex flex-col gap-1.5">
            <h1 class="titulo">Libro de estupefacientes y psicotrópicos</h1>
            <p class="text-sm text-secundario">Cada registro guarda el hash del anterior: si alguien altera una fila, la cadena se rompe.</p>
        </div>
        <a href="{{ route('controlados.verificar') }}" class="btn-g">Verificar integridad</a>
    </div>

    @cannot('firmar controlados')
        <p class="rounded-lg bg-primario-suave p-3 text-sm text-primario">Vista de solo lectura: tu rol no puede registrar ni firmar movimientos (separación de funciones).</p>
    @endcannot

    @can('firmar controlados')
        <div class="grid items-start gap-5 lg:grid-cols-2">
            <form method="POST" action="{{ route('controlados.egreso') }}" class="card flex flex-col gap-3.5 p-5">
                @csrf
                <h2 class="font-serif text-lg font-bold">Registrar egreso (firma del QF)</h2>
                <label class="lbl">Lote
                    <select class="inp" name="lote_id" required>
                        @foreach ($lotes as $lote)
                            <option value="{{ $lote->id }}">{{ $lote->producto->nombre }} · {{ $lote->numero_lote }} (stock {{ $lote->cantidad_actual }})</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid gap-3.5 sm:grid-cols-2">
                    <label class="lbl">Cantidad<input class="inp" type="number" min="1" name="cantidad" value="1" required></label>
                    <label class="lbl">Motivo<input class="inp" name="observacion" maxlength="255" required placeholder="Uso en box / merma"></label>
                </div>
                <label class="lbl">Tu contraseña (firma)
                    <input class="inp" type="password" name="password" required autocomplete="current-password">
                </label>
                <button class="btn-p self-end">Firmar y registrar</button>
            </form>

            <form method="POST" action="{{ route('controlados.conteo') }}" class="card flex flex-col gap-3.5 p-5">
                @csrf
                <h2 class="font-serif text-lg font-bold">Conteo físico</h2>
                <p class="text-sm text-secundario">Ingresa lo que contaste en el estante; si no coincide con el sistema se genera una alerta de descuadre.</p>
                <label class="lbl">Medicamento
                    <select class="inp" name="producto_id" required>
                        @foreach ($productos as $producto)
                            <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lbl">Cantidad contada<input class="inp" type="number" min="0" name="cantidad_fisica" required></label>
                <button class="btn-g self-end">Comparar con el sistema</button>
            </form>
        </div>
    @endcan

    <section class="card overflow-x-auto">
        <table class="w-full min-w-[860px] text-sm">
            <thead>
                <tr class="border-b border-borde text-left">
                    @foreach (['Fecha', 'Fármaco', 'Lote', 'Tipo', 'Cant.', 'Saldo', 'Registra', 'Autoriza', 'Hash'] as $col)
                        <th scope="col" class="th px-4 py-2.5">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($movimientos as $m)
                    <tr class="border-b border-[#EDF1EF]">
                        <td class="px-4 py-3 text-[13px]">{{ $m->created_at->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $m->lote->producto->nombre }}</td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $m->lote->numero_lote }}</td>
                        <td class="px-4 py-3"><span class="chip {{ $m->tipo === 'ingreso' ? 'bg-exito-suave text-exito' : 'bg-primario-suave text-primario' }}">{{ ucfirst($m->tipo) }}</span></td>
                        <td class="px-4 py-3">{{ $m->cantidad }}</td>
                        <td class="px-4 py-3">{{ $m->saldo_resultante }}</td>
                        <td class="px-4 py-3 text-[13px]">{{ $m->registrador->name }}</td>
                        <td class="px-4 py-3 text-[13px]">{{ $m->autorizador->name }}</td>
                        <td class="px-4 py-3 font-mono text-[13px]" title="{{ $m->hash }}">{{ substr($m->hash, 0, 10) }}…</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-6 text-center text-secundario">Aún no hay movimientos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    {{ $movimientos->links() }}
</x-layouts.app>
