<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Biblioteca Activa: consulta el acervo escolar, tus préstamos y reservaciones.">
    <title>Biblioteca Activa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
    <header class="border-b border-slate-200 bg-white/95">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8" aria-label="Navegación principal">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-display text-xl font-bold text-ink">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-600 text-white" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5zM20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5z"/></svg>
                </span>Biblioteca Activa
            </a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary">{{ auth()->check() ? 'Ir al panel' : 'Iniciar sesión' }}</a>
        </nav>
    </header>

    <main>
        <section class="relative overflow-hidden bg-slate-950 text-white">
            <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 80% 20%,#6366f1 0,transparent 35%),radial-gradient(circle at 10% 90%,#4f46e5 0,transparent 30%)"></div>
            <div class="relative mx-auto grid max-w-7xl gap-12 px-5 py-20 lg:grid-cols-[1.1fr_.9fr] lg:px-8 lg:py-28">
                <div>
                    <p class="eyebrow text-indigo-300">Biblioteca escolar · Comunidad UDG</p>
                    <h1 class="mt-5 max-w-3xl font-display text-5xl font-bold leading-[1.06] tracking-tight sm:text-6xl">El conocimiento circula cuando la biblioteca está activa.</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Explora el acervo, revisa disponibilidad y administra tu actividad desde un espacio claro, accesible y pensado para la vida académica.</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ auth()->check() ? route('catalog') : route('login') }}" class="btn-primary">Explorar el catálogo</a>
                        <a href="#servicios" class="btn-secondary border-slate-600 bg-transparent text-white hover:border-white hover:text-white">Conocer servicios</a>
                    </div>
                </div>
                <div class="card self-center border-slate-700 bg-slate-900/80 p-5 backdrop-blur">
                    <div class="rounded-xl bg-white p-5 text-slate-900">
                        <p class="text-sm font-semibold text-brand-700">Búsqueda académica</p>
                        <div class="mt-3 flex min-h-12 items-center gap-3 rounded-xl border border-slate-300 px-4 text-slate-500">
                            <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                            Título, autor, ISBN o unidad de aprendizaje
                        </div>
                        <div class="mt-5 space-y-3">
                            <div class="rounded-xl border border-slate-200 p-4"><span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Disponible</span><p class="mt-1 font-display text-lg font-bold">Consulta organizada del acervo</p><p class="text-sm text-slate-500">Clasificación, ubicación y ejemplares</p></div>
                            <div class="rounded-xl border border-slate-200 p-4"><span class="text-xs font-bold uppercase tracking-wider text-brand-700">Tu actividad</span><p class="mt-1 font-display text-lg font-bold">Préstamos y reservaciones</p><p class="text-sm text-slate-500">Fechas claras y seguimiento desde el panel</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="servicios" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
            <div class="max-w-2xl"><p class="eyebrow">Un sistema, tres experiencias</p><h2 class="mt-3 font-display text-4xl font-bold text-ink">Cada rol ve lo que necesita</h2><p class="mt-4 text-lg text-slate-600">Una experiencia modular para estudiantes, personal bibliotecario y administración.</p></div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <article class="card p-6"><p class="eyebrow">Estudiante</p><h3 class="mt-3 font-display text-2xl font-bold">Descubre y consulta</h3><p class="mt-3 leading-7 text-slate-600">Busca material, conoce disponibilidad y da seguimiento a préstamos y reservaciones.</p></article>
                <article class="card p-6"><p class="eyebrow">Bibliotecario</p><h3 class="mt-3 font-display text-2xl font-bold">Mantén el flujo</h3><p class="mt-3 leading-7 text-slate-600">Registra préstamos y devoluciones con trazabilidad y mensajes directos.</p></article>
                <article class="card p-6"><p class="eyebrow">Administrador</p><h3 class="mt-3 font-display text-2xl font-bold">Gobierna el acceso</h3><p class="mt-3 leading-7 text-slate-600">Administra usuarios, roles y estado general de la operación.</p></article>
            </div>
        </section>
    </main>
    <footer class="border-t border-slate-200 bg-slate-50"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:justify-between lg:px-8"><p>Biblioteca Activa · Proyecto académico UDG</p><p>Diseñada para acceso claro y responsable.</p></div></footer>
</body>
</html>
