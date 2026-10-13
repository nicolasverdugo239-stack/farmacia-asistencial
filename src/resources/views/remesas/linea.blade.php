<div data-linea class="grid gap-3 p-4 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_110px_auto] md:items-end">
    <label class="lbl">Producto
        <select class="inp" name="lineas[{{ $i }}][producto_id]" required>
            <option value="">Selecciona…</option>
            @foreach ($productos as $p)
                <option value="{{ $p->id }}" @selected(($l['producto_id'] ?? null) == $p->id)>
                    {{ $p->nombre }} ({{ $p->presentacion }}){{ $p->es_controlado ? ' · controlado' : '' }}
                </option>
            @endforeach
        </select>
    </label>
    <label class="lbl">N° de lote
        <input class="inp" name="lineas[{{ $i }}][numero_lote]" value="{{ $l['numero_lote'] ?? '' }}" maxlength="40" required>
    </label>
    <label class="lbl">Vencimiento
        <input class="inp" type="date" name="lineas[{{ $i }}][fecha_vencimiento]" value="{{ $l['fecha_vencimiento'] ?? '' }}" required>
    </label>
    <label class="lbl">Cantidad
        <input class="inp" type="number" min="1" name="lineas[{{ $i }}][cantidad]" value="{{ $l['cantidad'] ?? '' }}" required>
    </label>
    <button type="button" data-quitar class="btn-g btn-s" aria-label="Quitar lote">Quitar</button>
</div>
