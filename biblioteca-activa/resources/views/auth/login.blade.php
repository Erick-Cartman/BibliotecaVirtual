<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Biblioteca Activa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden bg-brand-600 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <a href="{{ route('home') }}" class="font-display text-2xl font-bold">Biblioteca Activa</a>
            <div class="max-w-xl"><p class="text-sm font-bold uppercase tracking-[.2em] text-indigo-200">Comunidad académica</p><h1 class="mt-5 font-display text-5xl font-bold leading-tight">Tu biblioteca, siempre en movimiento.</h1><p class="mt-5 text-lg leading-8 text-indigo-100">Consulta el acervo y gestiona la actividad bibliotecaria desde un solo lugar.</p></div>
            <p class="text-sm text-indigo-200">Acceso institucional · UDG</p>
        </section>
        <section class="flex items-center justify-center px-5 py-12">
            <div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl sm:p-9">
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
                <section aria-labelledby="demo-accounts-title" class="mt-7 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                    <h2 id="demo-accounts-title" class="font-bold text-ink">Cuentas de prueba de Yahir</h2>
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="font-semibold">Estudiante</dt>
                            <dd class="break-all">yahir@biblioteca.test</dd>
                            <dd>Contraseña: <code class="font-semibold">1234</code></dd>
                        </div>
                        <div>
                            <dt class="font-semibold">Administrador</dt>
                            <dd class="break-all">yahir.admin@biblioteca.test</dd>
                            <dd>Contraseña: <code class="font-semibold">1234</code></dd>
                        </div>
                        <div>
                            <dt class="font-semibold">Bibliotecario</dt>
                            <dd class="break-all">yahir.bibliotecario@biblioteca.test</dd>
                            <dd>Contraseña: <code class="font-semibold">1234</code></dd>
                        </div>
                    </dl>
                </section>
                <p class="mt-7 rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">¿Necesitas acceso? Solicítalo directamente al personal de biblioteca.</p>
            </div>
        </section>
    </main>
</body>
</html>
