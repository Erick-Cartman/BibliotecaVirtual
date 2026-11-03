@props(['book'])

@php
    $payloadLines = [
        'Biblioteca Virtual',
        'Ficha bibliografica',
        'Titulo: '.(string) ($book->title ?: 'Sin titulo'),
        'Autor(es): '.($book->authorLine() ?: 'No registrado'),
        'ISBN: '.(string) ($book->isbn ?: 'No registrado'),
        'Editorial: '.(string) ($book->publisher ?: 'No registrada'),
        'Anio de edicion: '.(string) ($book->publication_year ?: 'No registrado'),
        'Clasificacion: '.(string) ($book->dewey_code ?: 'Sin clasificacion'),
        'Unidad de aprendizaje: '.(string) ($book->learning_unit ?: 'No registrada'),
        'Programa: '.(string) ($book->program ?: 'General'),
        'Disponibilidad: '.(string) $book->available_copies.' de '.(string) $book->total_copies,
    ];
    $payloadText = implode("\n", $payloadLines);
@endphp

<div
    id="book-qr-{{ $book->getKey() }}"
    class="rounded-2xl border border-slate-200 bg-card p-4 text-center shadow-sm"
    wire:ignore
    x-data
    x-init="const renderQr = () => { window.QRCode?.toCanvas($refs.bookQr, @js($payloadText), { width: 256, margin: 4, errorCorrectionLevel: 'L', color: { dark: '#29211D', light: '#FFF8EF' } }); }; window.QRCode ? renderQr() : window.addEventListener('qrcode:ready', renderQr, { once: true });"
>
    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Ficha en código QR</p>
    <canvas
        x-ref="bookQr"
        width="256"
        height="256"
        class="mx-auto mt-3 size-64 max-w-full"
        aria-label="Código QR con los datos del libro {{ $book->title }}"
    ></canvas>
    <p class="mt-3 text-xs font-semibold text-slate-500">Escanéalo para consultar los datos del libro.</p>
    <span class="sr-only">Código QR de la ficha de {{ $book->title }}</span>
</div>
