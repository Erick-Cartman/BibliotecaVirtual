@props([
    'book',
    'loading' => 'lazy',
])

@php($coverUrl = $book->coverUrl())

<div
    x-data="{ coverFailed: false }"
    {{ $attributes->class(['relative isolate overflow-hidden bg-gradient-to-br from-brand-100 via-white to-amber-100']) }}
>
    @if ($coverUrl)
        <img
            x-show="! coverFailed"
            x-on:error="coverFailed = true"
            src="{{ $coverUrl }}"
            alt="Portada de {{ $book->title }}"
            class="absolute inset-0 size-full object-cover"
            loading="{{ $loading }}"
            decoding="async"
        >
    @endif

    <div
        @if ($coverUrl) x-cloak x-show="coverFailed" @endif
        class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-5 text-center"
        role="img"
        aria-label="Portada no disponible para {{ $book->title }}"
    >
        <span class="grid size-14 place-items-center rounded-2xl bg-white/80 text-brand-700 shadow-sm" aria-hidden="true">
            <svg viewBox="0 0 24 24" class="size-8 fill-none stroke-current" stroke-width="1.6">
                <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5zM20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5z"/>
            </svg>
        </span>
        <span class="line-clamp-4 font-display text-lg font-bold leading-snug text-ink">{{ $book->title }}</span>
        <span class="text-xs font-bold uppercase tracking-[0.14em] text-brand-700">Biblioteca Activa</span>
    </div>
</div>
