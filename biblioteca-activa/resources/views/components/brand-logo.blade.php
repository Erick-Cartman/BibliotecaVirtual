@props([
    'markClass' => 'h-12 w-36',
    'textClass' => 'text-sm text-brand-700',
    'imageClass' => '',
])

<div {{ $attributes->class('flex flex-col items-center') }} role="img" aria-label="Biblioteca Virtual">
    <span @class(['relative block overflow-hidden', $markClass]) aria-hidden="true">
        <img
            src="{{ asset('BA.png') }}"
            alt=""
            class="absolute inset-x-0 top-0 h-auto w-full max-w-none {{ $imageClass }}"
        >
    </span>
    <span @class(['mt-1 text-center font-display font-bold uppercase tracking-[0.12em] leading-none', $textClass])>
        <span class="block">Biblioteca</span>
        <span class="block">Virtual</span>
    </span>
</div>
