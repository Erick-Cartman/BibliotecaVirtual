<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
    <a href="#contenido" class="sr-only z-50 rounded bg-white p-3 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Saltar al contenido</a>
    <div class="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
        <aside class="border-b border-slate-200 bg-slate-950 px-5 py-5 text-white lg:min-h-screen lg:border-b-0 lg:border-r lg:border-slate-800">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3" aria-label="Biblioteca Activa, inicio">
                <span class="grid size-11 place-items-center rounded-xl bg-brand-600" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-6 fill-none stroke-current" stroke-width="1.8"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5zM20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5z"/></svg>
                </span>
                <span><span class="block font-display text-xl font-bold">Biblioteca Activa</span><span class="text-xs text-slate-400">Comunidad UDG</span></span>
            </a>
            <nav class="mt-7 flex gap-2 overflow-x-auto lg:flex-col" aria-label="Navegación principal">
                <a href="{{ route('dashboard') }}" wire:navigate @class(['rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-white text-slate-950' => request()->routeIs('dashboard'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('dashboard')])>Resumen</a>
                <a href="{{ route('catalog') }}" wire:navigate @class(['rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-white text-slate-950' => request()->routeIs('catalog'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('catalog')])>Catálogo</a>
                @if(auth()->user()->canManageLibrary())
                    <a href="{{ route('circulation') }}" wire:navigate @class(['rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-white text-slate-950' => request()->routeIs('circulation'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('circulation')])>Circulación</a>
                @endif
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('users') }}" wire:navigate @class(['rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-white text-slate-950' => request()->routeIs('users'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('users')])>Usuarios</a>
                @endif
            </nav>
            <div class="mt-7 hidden border-t border-slate-800 pt-5 lg:block">
                <p class="font-semibold">{{ auth()->user()->name }}</p>
                <p class="text-sm text-slate-400">{{ auth()->user()->role->label() }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="text-sm font-semibold text-slate-300 hover:text-white">Cerrar sesión</button></form>
            </div>
        </aside>
        <main id="contenido" class="min-w-0 px-4 py-6 sm:px-8 lg:px-10 lg:py-9">{{ $slot }}</main>
    </div>
    @livewireScripts
</body>
</html>
