<x-layouts.app titulo="Dispensación">
    <div class="flex flex-col gap-1.5">
        <h1 class="titulo">Receta N° {{ $receta->folio }}</h1>
        <p class="text-sm text-secundario">Revisa la validación, confirma el lote y registra quién retira.</p>
    </div>

    <form method="POST" action="{{ route('dispensaciones.store', $receta) }}" class="grid items-start gap-5 lg:grid-cols-2">
        @csrf
        <section class="card flex flex-col gap-4 p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-serif text-xl font-bold">Datos de la receta</h2>
                @if ($receta->programa)
                    <span class="chip bg-exito-suave text-exito">{{ $receta->programa->nombre }}</span>
                @endif
            </div>
            <dl class="grid grid-cols-2 gap-3.5">
                <div><dt class="th">Paciente</dt><dd class="mt-1 font-semibold">{{ $receta->paciente->nombre }}</dd></div>
                <div><dt class="th">RUT</dt><dd class="mt-1 font-mono text-sm">{{ $receta->paciente->rutEnmascarado() }}</dd></div>
                <div><dt class="th">Médico emisor</dt><dd class="mt-1">{{ $receta->medico->nombre }}</dd></div>
                <div><dt class="th">Vigencia</dt><dd class="mt-1">Hasta {{ $receta->fecha_vencimiento->format('d-m-Y') }}</dd></div>
            </dl>
            <ul class="flex flex-col gap-2 border-t border-[#EDF1EF] pt-3.5 text-sm">
                @foreach ([
                    ['RUT del paciente registrado', true],
                    ['Médico emisor registrado', (bool) $receta->medico],
                    ['Receta vigente', $receta->estaVigente()],
                    ['Cobertura sin costo', (bool) $receta->programa?->sin_costo],
                ] as [$texto, $ok])
                    <li class="flex items-center gap-2.5 {{ $ok ? '' : 'text-peligro' }}">
                        <span aria-hidden="true">{{ $ok ? '✓' : '✕' }}</span> {{ $texto }}{{ $ok ? '' : ' — no cumple' }}
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="flex flex-col gap-4">
            @foreach ($lineas as $linea)
                @php($d = $linea['detalle'])
                <fieldset class="card flex flex-col gap-2.5 p-5">
                    <legend class="sr-only">Lote para {{ $d->producto->nombre }}</legend>
                    <h2 class="font-serif text-lg font-bold">{{ $d->producto->nombre }} · {{ $d->cantidad }} u.</h2>
                    <p class="text-sm text-secundario">{{ $d->dosis }}. El sistema sugiere el lote que vence primero (FEFO).</p>
                    @forelse ($linea['lotes'] as $lote)
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-borde p-3 has-checked:border-primario has-checked:bg-primario-suave">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="lotes[{{ $d->producto_id }}]" value="{{ $lote->id }}"
                                       @checked($linea['sugerido']?->id === $lote->id)
                                       @disabled($lote->cantidad_actual < $d->cantidad)>
                                <span class="flex flex-col">
                                    <span class="font-mono font-bold">{{ $lote->numero_lote }}</span>
                                    <span class="text-[13px] text-secundario">Vence {{ $lote->fecha_vencimiento->format('d-m-Y') }} · stock {{ $lote->cantidad_actual }}</span>
                                </span>
                            </span>
                            @if ($linea['sugerido']?->id === $lote->id)
                                <span class="chip bg-primario text-white">Sugerido FEFO</span>
                            @endif
                        </label>
                    @empty
                        <p class="text-sm text-peligro">Sin lotes vigentes con stock.</p>
                    @endforelse
                    @if ($d->producto->es_controlado)
                        <p class="rounded-lg bg-aviso-suave p-2.5 text-sm text-aviso">Controlado: el egreso queda en el libro firmado por el Químico farmacéutico.</p>
                    @endif
                </fieldset>
            @endforeach

            <fieldset class="card flex flex-col gap-3.5 p-5">
                <legend class="sr-only">Quién retira</legend>
                <label class="flex items-center gap-3 font-semibold">
                    <input type="hidden" name="retira_tercero" value="0">
                    <input type="checkbox" id="retira_tercero" name="retira_tercero" value="1" class="size-5" @checked(old('retira_tercero'))>
                    Retira un tercero (no el paciente)
                </label>
                <div class="tercero-campos gap-3 sm:grid-cols-3">
                    <label class="lbl">Nombre<input class="inp" name="tercero_nombre" value="{{ old('tercero_nombre') }}" maxlength="120"></label>
                    <label class="lbl">RUT<input class="inp" name="tercero_rut" value="{{ old('tercero_rut') }}" maxlength="12" placeholder="12.345.678-9"></label>
                    <label class="lbl">Parentesco<input class="inp" name="tercero_parentesco" value="{{ old('tercero_parentesco') }}" maxlength="40" placeholder="Hijo/a"></label>
                </div>
            </fieldset>

            <div class="flex justify-end gap-3">
                <a href="{{ route('dispensaciones.buscar') }}" class="btn-g">Cancelar</a>
                <button type="submit" class="btn-p" @disabled(! $receta->estaVigente())>Confirmar entrega</button>
            </div>
        </div>
    </form>
</x-layouts.app>
