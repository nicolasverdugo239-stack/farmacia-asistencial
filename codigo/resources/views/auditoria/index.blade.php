<x-layouts.app titulo="Administración / Auditoría">
    <div class="flex flex-col gap-1.5">
        <h1 class="titulo">Bitácora de auditoría</h1>
        <p class="text-sm text-secundario">Últimos 100 eventos guardados en MongoDB. Los datos personales aparecen como [protegido].</p>
    </div>

    @if ($error)
        <p role="alert" class="rounded-lg bg-peligro-suave p-3 text-sm text-peligro">{{ $error }}</p>
    @endif

    <section class="card overflow-x-auto">
        <table class="w-full min-w-[860px] text-sm">
            <thead>
                <tr class="border-b border-borde text-left">
                    @foreach (['Fecha', 'Acción', 'Registro', 'Usuario', 'IP', 'Cambios'] as $col)
                        <th scope="col" class="th px-4 py-2.5">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($eventos as $e)
                    <tr class="border-b border-[#EDF1EF] align-top">
                        <td class="px-4 py-3 text-[13px] whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($e->fecha)->format('d-m-Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $e->accion }}</td>
                        <td class="px-4 py-3">{{ $e->modelo }} #{{ $e->modelo_id }}</td>
                        <td class="px-4 py-3">{{ $e->usuario_id ?? 'sistema' }}</td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $e->ip }}</td>
                        <td class="px-4 py-3 font-mono text-xs break-all">{{ json_encode($e->despues, JSON_UNESCAPED_UNICODE) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-secundario">Sin eventos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</x-layouts.app>
