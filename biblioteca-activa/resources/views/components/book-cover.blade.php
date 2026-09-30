@props([
    'book',
    'loading' => 'lazy',
])

@php($coverUrl = $book->coverUrl())
@php($coverHue = 200 + (abs(crc32((string) ($book->source_key ?: $book->title))) % 140))

<div
    {{ $attributes->class(['relative isolate overflow-hidden bg-gradient-to-br from-brand-100 via-white to-amber-100']) }}
>
    @if ($coverUrl)
        <img
            onerror="this.hidden = true; this.nextElementSibling.hidden = false;"
            src="{{ $coverUrl }}"
            alt="Portada de {{ $book->title }}"
            class="absolute inset-0 size-full object-cover"
            loading="{{ $loading }}"
            decoding="async"
        >
    @endif

    <div
        @if ($coverUrl) hidden @endif
        class="book-cover-fallback absolute inset-0 flex flex-col items-center justify-center gap-3 p-5 text-center"
        style="--book-cover-hue: {{ $coverHue }};"
        role="img"
        aria-label="Portada genérica de {{ $book->title }}"
    >
        <span class="book-cover-fallback__badge">Portada genérica</span>
        <span class="book-cover-fallback__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" class="size-8 fill-none stroke-current" stroke-width="1.6">
                <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5zM20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5z"/>
            </svg>
        </span>
        <span class="book-cover-fallback__title">{{ $book->title }}</span>
        <span class="book-cover-fallback__footer">Biblioteca Activa</span>
    </div>
</div>
