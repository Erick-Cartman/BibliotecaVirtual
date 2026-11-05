@props([
    'book',
    'loading' => 'lazy',
])

@php($coverUrl = $book->coverUrl())
@php
    preg_match('/^\s*(\d{1,3})/', (string) ($book->dewey_code ?? ''), $deweyMatch);
    $coverCategory = isset($deweyMatch[1])
        ? min(9, intdiv((int) $deweyMatch[1], 100))
        : abs(crc32((string) ($book->source_key ?: $book->title))) % 10;
    $coverPalettes = [
        0 => ['ink' => '#243b53', 'accent' => '#4f91b9', 'paper' => '#edf5fa'],
        1 => ['ink' => '#40336f', 'accent' => '#8068b9', 'paper' => '#f2effa'],
        2 => ['ink' => '#70420e', 'accent' => '#c58b26', 'paper' => '#fbf3df'],
        3 => ['ink' => '#155264', 'accent' => '#298a9e', 'paper' => '#e8f5f6'],
        4 => ['ink' => '#175b4c', 'accent' => '#39947a', 'paper' => '#e9f5ef'],
        5 => ['ink' => '#36562c', 'accent' => '#779b45', 'paper' => '#f0f5e8'],
        6 => ['ink' => '#793d22', 'accent' => '#c16b3b', 'paper' => '#fbefe7'],
        7 => ['ink' => '#79304f', 'accent' => '#bd5f84', 'paper' => '#faedf2'],
        8 => ['ink' => '#702e32', 'accent' => '#bb5a55', 'paper' => '#fbefeb'],
        9 => ['ink' => '#25466d', 'accent' => '#527eae', 'paper' => '#edf2f8'],
    ];
    $coverPalette = $coverPalettes[$coverCategory];
    $coverCategoryNames = [
        0 => 'Conocimiento',
        1 => 'Filosofía y psicología',
        2 => 'Religión',
        3 => 'Ciencias sociales',
        4 => 'Lenguas',
        5 => 'Ciencias',
        6 => 'Tecnología',
        7 => 'Artes',
        8 => 'Literatura',
        9 => 'Historia y geografía',
    ];
    $coverTopic = filled($book->learning_unit ?? null)
        ? $book->learning_unit
        : (filled($book->program ?? null) ? $book->program : $coverCategoryNames[$coverCategory]);
    $coverAuthor = $book->authorLine();
@endphp

<div {{ $attributes->class(['relative isolate overflow-hidden bg-gradient-to-br from-brand-100 via-card to-brand-50']) }}>
    @if ($coverUrl)
        <img
            onload="if (this.naturalWidth >= Math.max(360, this.parentElement.clientWidth * 1.5) && this.naturalHeight >= Math.max(480, this.parentElement.clientHeight * 1.5)) { this.hidden = false; this.nextElementSibling.hidden = true; }"
            onerror="this.hidden = true; this.nextElementSibling.hidden = false;"
            src="{{ $coverUrl }}"
            alt="Portada de {{ $book->title }}"
            class="absolute inset-0 size-full object-contain"
            loading="{{ $loading }}"
            decoding="async"
            hidden
        >
    @endif

    <div
        class="book-cover-fallback absolute inset-0 flex flex-col"
        style="--book-cover-ink: {{ $coverPalette['ink'] }}; --book-cover-accent: {{ $coverPalette['accent'] }}; --book-cover-paper: {{ $coverPalette['paper'] }};"
        role="img"
        aria-label="Portada diseñada para {{ $book->title }}{{ $coverAuthor ? ', de '.$coverAuthor : '' }}"
    >
        <span class="book-cover-fallback__frame" aria-hidden="true"></span>
        <svg class="book-cover-fallback__art" viewBox="0 0 320 260" fill="none" aria-hidden="true">
            <circle cx="243" cy="91" r="70" stroke="currentColor" stroke-opacity=".2" stroke-width="1.5"/>
            <circle cx="243" cy="91" r="49" stroke="currentColor" stroke-opacity=".18" stroke-width="1.5"/>
            @switch($coverCategory)
                @case(0)
                    <path d="M83 186 132 132l42 25 58-74 40 36" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="83" cy="186" r="7" fill="currentColor"/><circle cx="132" cy="132" r="7" fill="currentColor"/><circle cx="174" cy="157" r="7" fill="currentColor"/><circle cx="232" cy="83" r="7" fill="currentColor"/><circle cx="272" cy="119" r="7" fill="currentColor"/>
                    @break
                @case(1)
                    <circle cx="160" cy="130" r="73" stroke="currentColor" stroke-width="2"/><circle cx="160" cy="130" r="48" stroke="currentColor" stroke-width="2"/><circle cx="160" cy="130" r="22" fill="currentColor" fill-opacity=".2" stroke="currentColor" stroke-width="2"/>
                    <path d="M160 40v18m0 144v18M70 130h18m144 0h18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    @break
                @case(2)
                    <path d="M85 204V127a75 75 0 0 1 150 0v77M108 204v-77a52 52 0 0 1 104 0v77" stroke="currentColor" stroke-width="3"/>
                    <path d="M160 42v27m-57-4 14 23m-48 20 27 9m151-52-14 23m48 20-27 9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    @break
                @case(3)
                    <circle cx="115" cy="109" r="22" fill="currentColor" fill-opacity=".2" stroke="currentColor" stroke-width="2.5"/><circle cx="205" cy="109" r="22" fill="currentColor" fill-opacity=".2" stroke="currentColor" stroke-width="2.5"/><circle cx="160" cy="78" r="25" fill="currentColor" fill-opacity=".28" stroke="currentColor" stroke-width="2.5"/>
                    <path d="M69 190c3-27 20-42 46-42s43 15 46 42m-2 21c3-32 23-50 51-50s48 18 51 50" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    @break
                @case(4)
                    <path d="M69 83a25 25 0 0 1 25-25h94a25 25 0 0 1 25 25v31a25 25 0 0 1-25 25h-41l-34 27v-27H94a25 25 0 0 1-25-25z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M130 164h72a24 24 0 0 1 24 24v10a24 24 0 0 1-24 24h-19l-27 20v-20h-26a24 24 0 0 1-24-24" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                    @break
                @case(5)
                    <ellipse cx="160" cy="130" rx="91" ry="39" stroke="currentColor" stroke-width="3"/><ellipse cx="160" cy="130" rx="91" ry="39" transform="rotate(60 160 130)" stroke="currentColor" stroke-width="3"/><ellipse cx="160" cy="130" rx="91" ry="39" transform="rotate(120 160 130)" stroke="currentColor" stroke-width="3"/>
                    <circle cx="160" cy="130" r="11" fill="currentColor"/><circle cx="251" cy="130" r="6" fill="currentColor"/><circle cx="115" cy="52" r="6" fill="currentColor"/>
                    @break
                @case(6)
                    <path d="M72 74h68v68H72zM180 74h68v68h-68zM72 182h68v38H72zM180 182h68v38h-68z" stroke="currentColor" stroke-width="3"/>
                    <path d="M91 94h30m-30 15h30m-30 15h20m78-24h31m-31 15h31m-31 15h22m-99 55h30m-30 15h21m57-15h31m-31 15h21" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    @break
                @case(7)
                    <path d="M72 174c29-95 75-123 120-83 28 25 41 83 73 50M72 202c44 8 78-23 106-57 35-44 64-54 87-37" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="96" cy="76" r="19" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5"/><path d="m229 172 12 21 24 4-17 17 4 24-23-11-22 11 4-24-18-17 25-4z" fill="currentColor" fill-opacity=".2" stroke="currentColor" stroke-width="2"/>
                    @break
                @case(8)
                    <path d="M160 77c-28-19-58-20-91-7v131c32-13 62-12 91 7 29-19 59-20 91-7V70c-33-13-63-12-91 7Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M160 77v131M91 101c17-5 33-5 49 1m-49 22c17-5 33-5 49 1m50-23c17-6 33-6 49-1m-49 23c17-6 33-6 49-1" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    @break
                @default
                    <circle cx="160" cy="130" r="84" stroke="currentColor" stroke-width="3"/><path d="M76 130h168M160 46a127 127 0 0 1 0 168M160 46a127 127 0 0 0 0 168M91 85h138m-138 90h138" stroke="currentColor" stroke-width="2.5"/>
                    <path d="m106 167 33-31 21 16 35-43 21 18" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            @endswitch
        </svg>
        <div class="book-cover-fallback__content">
            <p class="book-cover-fallback__brand">Biblioteca Activa</p>
            <p class="book-cover-fallback__topic">{{ $coverTopic }}</p>
            <h2 class="book-cover-fallback__title">{{ $book->title }}</h2>
            @if ($coverAuthor)
                <p class="book-cover-fallback__author">{{ $coverAuthor }}</p>
            @endif
            @if (filled($book->dewey_code ?? null))
                <p class="book-cover-fallback__classification">{{ $book->dewey_code }}</p>
            @endif
        </div>
    </div>
</div>
