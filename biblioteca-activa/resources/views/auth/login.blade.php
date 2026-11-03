<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Biblioteca Virtual</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-sidebar">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden bg-hero p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="max-w-xl">
                <a href="{{ route('home') }}" class="mx-auto mb-8 flex w-fit" aria-label="Biblioteca Virtual, inicio">
                    <x-brand-logo mark-class="h-20 w-60" text-class="text-lg text-white" image-class="brightness-0 invert" />
                </a>
                <p class="text-sm font-bold uppercase tracking-[.2em] text-brand-200">Comunidad académica</p><h1 class="mt-5 font-display text-5xl font-bold leading-tight">Tu biblioteca, siempre en movimiento.</h1><p class="mt-5 text-lg leading-8 text-brand-100">Consulta el acervo y gestiona la actividad bibliotecaria desde un solo lugar.</p>
                <img src="{{ asset('school-reader.png') }}" alt="Persona leyendo un libro en el patio de una escuela" class="mt-8 h-56 w-full rounded-2xl object-cover shadow-2xl ring-1 ring-white/15" loading="eager" decoding="async">
                <p class="mt-5 text-base font-medium leading-7 text-brand-100">Leer amplía tus ideas, fortalece tu imaginación y te ayuda a comprender mejor el mundo.</p>
            </div>
            <p class="text-sm text-brand-200">Acceso institucional · UDG</p>
        </section>
        <section class="flex items-center justify-center px-5 py-12">
            <div class="w-full max-w-md rounded-3xl bg-card p-7 shadow-2xl sm:p-9">
                <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-700">← Volver al inicio</a>
                <p class="eyebrow mt-9">Acceso seguro</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-ink">Inicia sesión</h1>
                <p class="mt-2 text-slate-600">Utiliza las credenciales asignadas por la biblioteca.</p>
                <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                    @csrf
                    <div><label class="label" for="email">Correo electrónico</label><input class="field" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>@error('email')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="password">Contraseña</label><input class="field" id="password" name="password" type="password" autocomplete="current-password" required>@error('password')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror</div>
                    <label class="flex items-center gap-3 text-sm text-slate-700"><input class="size-4 rounded border-slate-300 text-brand-600" type="checkbox" name="remember" value="1">Mantener la sesión iniciada</label>
                    <button class="btn-primary w-full" type="submit">Ingresar al panel</button>
                </form>
                <p class="mt-7 rounded-xl bg-surface p-4 text-sm leading-6 text-slate-600">¿Necesitas acceso? Solicítalo directamente al personal de biblioteca.</p>
            </div>
        </section>
    </main>
</body>
</html>
