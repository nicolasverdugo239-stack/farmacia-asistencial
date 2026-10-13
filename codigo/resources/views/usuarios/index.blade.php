<x-layouts.app titulo="Administración / Usuarios">
    <div class="flex flex-col gap-1.5">
        <h1 class="titulo">Usuarios y roles</h1>
        <p class="text-sm text-secundario">Solo el Administrador crea o desactiva usuarios. Cada usuario tiene un único rol.</p>
    </div>

    <section class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-borde text-left">
                    @foreach (['Nombre', 'RUT', 'Correo', 'Rol', 'Estado', ''] as $col)
                        <th scope="col" class="th px-4 py-2.5">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $u)
                    <tr class="border-b border-[#EDF1EF]">
                        <td class="px-4 py-3 font-semibold">{{ $u->name }}</td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $u->rut }}</td>
                        <td class="px-4 py-3">{{ $u->email }}</td>
                        <td class="px-4 py-3">{{ ['administrador' => 'Administrador', 'tecnico' => 'Técnico farmacéutico', 'quimico' => 'Químico farmacéutico'][$u->getRoleNames()->first()] ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="chip {{ $u->activo ? 'bg-exito-suave text-exito' : 'bg-fondo text-secundario' }}">{{ $u->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @unless ($u->is(auth()->user()))
                                <form method="POST" action="{{ route('usuarios.toggle', $u) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn-g btn-s">{{ $u->activo ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <form method="POST" action="{{ route('usuarios.store') }}" class="card grid gap-4 p-5 md:grid-cols-3">
        @csrf
        <h2 class="font-serif text-lg font-bold md:col-span-3">Nuevo usuario</h2>
        <label class="lbl">RUT<input class="inp" name="rut" value="{{ old('rut') }}" maxlength="12" required></label>
        <label class="lbl">Nombre<input class="inp" name="name" value="{{ old('name') }}" maxlength="120" required></label>
        <label class="lbl">Correo<input class="inp" type="email" name="email" value="{{ old('email') }}" required></label>
        <label class="lbl">Rol
            <select class="inp" name="rol" required>
                <option value="tecnico">Técnico farmacéutico</option>
                <option value="quimico">Químico farmacéutico</option>
                <option value="administrador">Administrador</option>
            </select>
        </label>
        <label class="lbl">Contraseña<input class="inp" type="password" name="password" required autocomplete="new-password"></label>
        <label class="lbl">Repetir contraseña<input class="inp" type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <p class="text-xs text-secundario md:col-span-2">Mínimo 12 caracteres con mayúsculas, minúsculas, números y símbolos.</p>
        <button class="btn-p md:justify-self-end">Crear usuario</button>
    </form>
</x-layouts.app>
