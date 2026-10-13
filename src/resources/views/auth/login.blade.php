<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Farmacia Asistencial</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-fondo font-sans text-tinta antialiased">
<div class="flex min-h-screen flex-col md:flex-row">
    <section class="flex flex-col justify-between gap-10 bg-primario p-8 text-white md:w-[42%] md:p-16">
        <span class="flex items-center gap-3 font-bold">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/></svg>
            Farmacia Asistencial
        </span>
        <div class="flex flex-col gap-4">
            <h1 class="font-serif text-3xl leading-tight font-bold md:text-5xl">Stock, recetas y controlados en un solo lugar.</h1>
            <p class="text-[#D6E8EE]">Área de simulación de farmacia · INACAP Temuco</p>
        </div>
        <p class="text-sm text-[#BFD9E2]">Conexión cifrada · Acceso según rol · Registro de auditoría</p>
    </section>

    <main class="flex flex-1 items-center justify-center p-6">
        <form method="POST" action="{{ url('/login') }}" class="card flex w-full max-w-md flex-col gap-5 p-8 md:p-10">
            @csrf
            <h2 class="titulo">Iniciar sesión</h2>
            @include('partials.flash')
            <label class="lbl">Correo institucional
                <input class="inp" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </label>
            <label class="lbl">Contraseña
                <input class="inp" type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="btn-p">Ingresar</button>
            <p class="text-xs text-secundario">Tras 5 intentos fallidos el acceso se bloquea 15 minutos.</p>
        </form>
    </main>
</div>
</body>
</html>
