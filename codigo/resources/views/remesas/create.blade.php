<x-layouts.app titulo="Inventario / Remesas">
    <div class="flex flex-col gap-1.5">
        <h1 class="titulo">Recepción de remesa CENABAST</h1>
        <p class="text-sm text-secundario">Cada lote entra con su número y vencimiento; el stock se actualiza al confirmar.</p>
    </div>

    <form method="POST" action="{{ route('remesas.store') }}" class="flex flex-col gap-5">
        @csrf
        <div class="card grid gap-4 p-5 md:grid-cols-3">
            <label class="lbl">N° guía de despacho
                <input class="inp" name="numero_guia" value="{{ old('numero_guia') }}" maxlength="40" required placeholder="GD-2026-0931">
            </label>
            <label class="lbl">Fecha de recepción
                <input class="inp" value="{{ now()->format('d-m-Y') }}" readonly>
            </label>
            <label class="lbl">Recibido por
                <input class="inp" value="{{ auth()->user()->name }}" readonly>
            </label>
        </div>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-borde px-5 py-4">
                <h2 class="font-serif text-lg font-bold">Lotes recibidos</h2>
                <button type="button" id="agregar-linea" class="btn-g btn-s">Agregar lote</button>
            </div>
            <div id="lineas" class="flex flex-col divide-y divide-[#EDF1EF]">
                @foreach (old('lineas', [[]]) as $i => $l)
                    @include('remesas.linea', ['i' => $i, 'l' => $l])
                @endforeach
            </div>
        </section>

        <div class="flex justify-end"><button class="btn-p">Confirmar recepción</button></div>
    </form>

    <template id="linea-tpl">
        @include('remesas.linea', ['i' => '__i__', 'l' => []])
    </template>
</x-layouts.app>
