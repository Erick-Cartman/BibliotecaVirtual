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
            @php
                $profileUser = auth()->user();
                $profileNameParts = preg_split('/\s+/', trim((string) $profileUser->name), 2);
                $profileFirstName = old('first_name', $profileUser->first_name ?: ($profileNameParts[0] ?? ''));
                $profileLastName = old('last_name', $profileUser->last_name ?: ($profileNameParts[1] ?? ''));
            @endphp
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
            <div class="mt-7 border-t border-slate-800 pt-5">
                <button type="button" class="group flex w-full cursor-pointer items-center gap-3 rounded-xl p-2 text-left transition hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500" aria-label="Ver datos personales" aria-haspopup="dialog" aria-controls="profile-view-dialog" onclick="document.getElementById('profile-view-dialog').showModal()">
                    @if($profileUser->avatar_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profileUser->avatar_path) }}" alt="Foto de {{ $profileUser->name }}" class="size-11 rounded-full object-cover ring-2 ring-slate-700 transition group-hover:scale-105 group-hover:ring-brand-500">
                    @else
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-600 font-display text-lg font-bold transition group-hover:bg-brand-500 group-hover:ring-2 group-hover:ring-brand-300" aria-hidden="true">{{ str($profileUser->name)->substr(0, 1)->upper() }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate font-semibold transition group-hover:text-white">{{ $profileUser->name }}</p>
                        <p class="text-sm text-slate-400 transition group-hover:text-slate-200">{{ $profileUser->role->label() }}</p>
                    </div>
                </button>
                <button type="button" class="mt-4 flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white" onclick="document.getElementById('profile-settings-dialog').showModal()">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="m19.4 15 .1.1a2 2 0 0 1-2.8 2.8l-.1-.1a2 2 0 0 0-3.4 1.4v.2a2 2 0 0 1-4 0v-.2a2 2 0 0 0-3.4-1.4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A2 2 0 0 0 1.6 12a2 2 0 0 0 1.4-3.4l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A2 2 0 0 0 9.2 4.4V4.2a2 2 0 0 1 4 0v.2a2 2 0 0 0 3.4 1.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A2 2 0 0 0 20.8 12a2 2 0 0 0-1.4 3.4Z"/></svg>
                    Editar datos personales
                </button>
                @if(session('profile_message'))
                    <p class="mt-3 rounded-lg bg-emerald-500/15 px-3 py-2 text-xs font-medium text-emerald-200" role="status">{{ session('profile_message') }}</p>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="text-sm font-semibold text-slate-300 hover:text-white">Cerrar sesión</button></form>
            </div>
        </aside>
        <main id="contenido" class="min-w-0 px-4 py-6 sm:px-8 lg:px-10 lg:py-9">{{ $slot }}</main>
    </div>
    <dialog id="profile-view-dialog" aria-labelledby="profile-view-title" class="w-[calc(100%-2rem)] max-w-lg rounded-3xl border-0 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/70">
        <div class="p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="eyebrow">Mi perfil</p>
                    <h2 id="profile-view-title" class="mt-2 font-display text-3xl font-bold text-ink">Datos personales</h2>
                    <p class="mt-2 text-sm text-slate-600">Esta ventana es solo de consulta.</p>
                </div>
                <button type="button" class="grid size-10 shrink-0 place-items-center rounded-full text-2xl text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Cerrar datos personales" onclick="document.getElementById('profile-view-dialog').close()">&times;</button>
            </div>
            <div class="mt-7 flex items-center gap-4 rounded-2xl bg-slate-50 p-4">
                @if($profileUser->avatar_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profileUser->avatar_path) }}" alt="Foto de {{ $profileUser->name }}" class="size-16 rounded-full object-cover ring-2 ring-brand-100">
                @else
                    <span class="grid size-16 shrink-0 place-items-center rounded-full bg-brand-600 font-display text-2xl font-bold text-white" aria-hidden="true">{{ str($profileUser->name)->substr(0, 1)->upper() }}</span>
                @endif
                <div>
                    <p class="font-display text-xl font-bold text-ink">{{ $profileUser->name }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $profileUser->role->label() }}</p>
                </div>
            </div>
            <dl class="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[9rem_1fr] sm:items-center">
                    <dt class="text-sm font-semibold text-slate-500">Nombre</dt>
                    <dd class="font-medium text-slate-900">{{ $profileFirstName }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[9rem_1fr] sm:items-center">
                    <dt class="text-sm font-semibold text-slate-500">Apellido</dt>
                    <dd class="font-medium text-slate-900">{{ $profileLastName }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[9rem_1fr] sm:items-center">
                    <dt class="text-sm font-semibold text-slate-500">Correo</dt>
                    <dd class="break-all font-medium text-slate-900">{{ $profileUser->email }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[9rem_1fr] sm:items-center">
                    <dt class="text-sm font-semibold text-slate-500">Contraseña</dt>
                    <dd class="font-medium tracking-widest text-slate-900">••••••••</dd>
                </div>
            </dl>
            <button type="button" class="btn-secondary mt-6 w-full" onclick="document.getElementById('profile-view-dialog').close()">Cerrar</button>
        </div>
    </dialog>
    <dialog id="profile-settings-dialog" aria-labelledby="profile-settings-title" class="w-[calc(100%-2rem)] max-w-2xl rounded-3xl border-0 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/70">
        <div class="max-h-[90vh] overflow-y-auto p-6 sm:p-8">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="eyebrow">Mi perfil</p>
                        <h2 id="profile-settings-title" class="mt-2 font-display text-3xl font-bold text-ink">Datos personales</h2>
                        <p class="mt-2 text-sm text-slate-600">Actualiza la información con la que te identifica la biblioteca.</p>
                    </div>
                    <button type="button" class="grid size-10 shrink-0 place-items-center rounded-full text-2xl text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Cerrar ventana" onclick="document.getElementById('profile-settings-dialog').close()">&times;</button>
                </div>
                <div class="mt-7 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label" for="profile-first-name">Nombre</label>
                        <input id="profile-first-name" class="field" name="first_name" value="{{ $profileFirstName }}" autocomplete="given-name" required>
                        @error('first_name')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="profile-last-name">Apellido</label>
                        <input id="profile-last-name" class="field" name="last_name" value="{{ $profileLastName }}" autocomplete="family-name" required>
                        @error('last_name')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="profile-email">Correo electrónico</label>
                        <input id="profile-email" class="field" name="email" type="email" value="{{ old('email', $profileUser->email) }}" autocomplete="email" required>
                        @error('email')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="profile-password">Nueva contraseña</label>
                        <input id="profile-password" class="field" name="password" type="password" autocomplete="new-password" minlength="8">
                        <p class="mt-1.5 text-xs text-slate-500">Déjala vacía para conservar tu contraseña actual. Mínimo 8 caracteres.</p>
                        @error('password')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="profile-avatar">Foto de perfil</label>
                        <input id="profile-avatar" class="field file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700" name="avatar" type="file" accept=".jpg,.jpeg,image/jpeg">
                        <p class="mt-1.5 text-xs text-slate-500">Solo archivos JPG o JPEG de hasta 2 MB.</p>
                        @error('avatar')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('profile-settings-dialog').close()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </dialog>
    @if(session('profile_modal_open'))
        <script>
            const openProfileSettings = () => document.getElementById('profile-settings-dialog')?.showModal();
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', openProfileSettings);
            } else {
                openProfileSettings();
            }
        </script>
    @endif
    @livewireScripts
</body>
</html>
