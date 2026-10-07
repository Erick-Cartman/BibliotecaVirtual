<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Biblioteca Activa: consulta el acervo escolar, tus préstamos y reservaciones.">
    <title>Biblioteca Activa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface">
    <header class="border-b border-slate-200 bg-ivory/95">
        <nav class="relative mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8" aria-label="Navegación principal">
            <a href="{{ route('home') }}" class="flex items-center" aria-label="Biblioteca Activa, inicio">
                <img src="{{ asset('BA.png') }}" alt="Biblioteca Activa" class="-ml-3 h-20 w-auto shrink-0 object-contain">
            </a>
            <span class="absolute left-1/2 hidden -translate-x-1/2 font-display text-2xl font-bold tracking-tight text-brand-700 sm:block sm:text-3xl">Biblioteca Activa</span>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary">{{ auth()->check() ? 'Ir al panel' : 'Iniciar sesión' }}</a>
        </nav>
    </header>

    <main>
        <section class="relative overflow-hidden bg-hero text-white">
            <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 80% 20%,#B77945 0,transparent 35%),radial-gradient(circle at 10% 90%,#9A633A 0,transparent 30%)"></div>
            <div class="relative mx-auto grid max-w-7xl gap-12 px-5 py-20 lg:grid-cols-[1.1fr_.9fr] lg:px-8 lg:py-28">
                <div>
                    <p class="eyebrow text-brand-200">Biblioteca escolar · Comunidad UDG</p>
                    <h1 class="mt-5 max-w-3xl font-display text-5xl font-bold leading-[1.06] tracking-tight sm:text-6xl">El conocimiento circula cuando la biblioteca está activa.</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Explora el acervo, revisa disponibilidad y administra tu actividad desde un espacio claro, accesible y pensado para la vida académica.</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ auth()->check() ? route('catalog') : route('login') }}" class="btn-primary">Explorar el catálogo</a>
                        <a href="#servicios" class="btn-secondary border-slate-600 bg-transparent text-white hover:border-white hover:text-white">Conocer servicios</a>
                    </div>
                </div>
                @php($featuredBook = $featuredBooks->first())
                <div
                    class="card self-center overflow-hidden border-ivory bg-ivory p-5 text-ink backdrop-blur sm:p-6"
                    data-book-viewer
                    role="region"
                    aria-labelledby="featured-book-title"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-brand-700">Visor 3D del acervo</p>
                            <h2 id="featured-book-title" class="mt-2 font-display text-2xl font-bold text-ink">LIBRO VIRTUAL</h2>
                            <p class="mt-2 max-w-sm text-sm leading-6 text-secondary">Una selección real de la base de datos girando suavemente en 360 grados.</p>
                        </div>
                        @if ($featuredBook)
                            <span class="shrink-0 rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-700">1 selección</span>
                        @endif
                    </div>

                    @if ($featuredBook)
                        <div class="book-stage mt-6" data-book-stage aria-describedby="featured-book-description">
                            <div class="book-stage__grid" aria-hidden="true"></div>
                            <div class="book-stage__glow" aria-hidden="true"></div>
                            <div class="book-stage__shadow" aria-hidden="true"></div>
                            <div
                                class="book-3d"
                                data-book-3d
                                role="img"
                                aria-label="Vista 3D de {{ $featuredBook->title }} girando 360 grados"
                            >
                                <div class="book-3d__face book-3d__back" aria-hidden="true">
                                    <span class="book-3d__back-mark">Biblioteca<br>Activa</span>
                                </div>
                                <div class="book-3d__face book-3d__spine" aria-hidden="true">
                                    <span>{{ $featuredBook->title }}</span>
                                </div>
                                <div class="book-3d__face book-3d__pages book-3d__pages--top" aria-hidden="true"></div>
                                <div class="book-3d__face book-3d__pages book-3d__pages--bottom" aria-hidden="true"></div>
                                <div class="book-3d__face book-3d__pages book-3d__pages--edge" aria-hidden="true"></div>
                                <div class="book-3d__face book-3d__front">
                                    <x-book-cover :book="$featuredBook" loading="eager" class="size-full rounded-none shadow-2xl ring-1 ring-white/15" />
                                    <span class="absolute inset-x-3 bottom-3 rounded-lg bg-slate-950/80 px-2 py-1.5 text-center text-[0.625rem] font-bold uppercase tracking-[0.16em] text-emerald-300 backdrop-blur-sm">Disponible</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-700">Selección de hoy</p>
                                <a
                                    href="{{ auth()->check() ? route('catalog') : route('login') }}"
                                    class="mt-1 block truncate font-display text-xl font-bold text-ink underline-offset-4 transition hover:text-brand-700 hover:underline focus-visible:ring-2 focus-visible:ring-brand-200"
                                    aria-label="Consultar {{ $featuredBook->title }} en el catálogo"
                                >{{ $featuredBook->title }}</a>
                                <p class="mt-1 truncate text-sm text-secondary">{{ $featuredBook->authorLine() ?: 'Autor no registrado' }}</p>
                            </div>
                        </div>
                        <p id="featured-book-description" class="mt-4 text-xs leading-5 text-secondary">El libro gira suavemente en 360 grados; la rotación también se desactiva si tu dispositivo solicita menos movimiento.</p>
                    @else
                        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 px-5 py-8 text-sm leading-6 text-secondary">
                            El visor 3D aparecerá cuando el acervo esté disponible.
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section id="servicios" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
            <div class="max-w-2xl"><p class="eyebrow">Un sistema, tres experiencias</p><h2 class="mt-3 font-display text-4xl font-bold text-ink">Explora, aprende, conectate.</h2><p class="mt-4 text-lg text-slate-600">Una experiencia modular para estudiantes, personal bibliotecario y administración.</p></div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <article class="card p-6"><p class="eyebrow">Estudiante</p><h3 class="mt-3 font-display text-2xl font-bold">Descubre y consulta</h3><p class="mt-3 leading-7 text-slate-600">Busca material, conoce disponibilidad y da seguimiento a préstamos y reservaciones.</p></article>
                <article class="card p-6"><p class="eyebrow">Bibliotecario</p><h3 class="mt-3 font-display text-2xl font-bold">Mantén el flujo</h3><p class="mt-3 leading-7 text-slate-600">Registra préstamos y devoluciones con trazabilidad y mensajes directos.</p></article>
                <article class="card p-6"><p class="eyebrow">Administrador</p><h3 class="mt-3 font-display text-2xl font-bold">Gobierna el acceso</h3><p class="mt-3 leading-7 text-slate-600">Administra usuarios, roles y estado general de la operación.</p></article>
            </div>
        </section>
    </main>
    <footer class="border-t border-slate-200 bg-surface"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:justify-between lg:px-8"><p>Biblioteca Activa · Proyecto académico UDG</p><p>Diseñada para acceso claro y responsable.</p></div></footer>
</body>
</html>
