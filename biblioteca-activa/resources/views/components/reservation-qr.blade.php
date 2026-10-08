@props([
    'reservation',
    'studentEmail' => null,
])

@php
    $studentName = $reservation->getAttribute('student_name_display') ?: $reservation->student_name;
    $studentCode = $reservation->getAttribute('student_code_display') ?: $reservation->student_code;
    $reservationStudentEmail = $reservation->getAttribute('student_email_display') ?: $studentEmail;
    $payloadLines = [
        'Biblioteca Activa',
        'Reservacion: '.(string) $reservation->getKey(),
        'Alumno: '.(string) ($studentName ?: 'Alumno'),
        'Codigo de alumno: '.(string) ($studentCode ?: 'Sin codigo'),
        'Libro: '.(string) ($reservation->book_title ?: 'Libro'),
        'Estado: '.($reservation->status?->label() ?? 'Sin estado'),
        'Fecha: '.($reservation->reserved_at?->format('d/m/Y H:i') ?? 'Sin fecha'),
    ];

    if ($reservationStudentEmail) {
        $payloadLines[] = 'Correo: '.(string) $reservationStudentEmail;
    }

    $payloadText = implode("\n", $payloadLines);
@endphp

<div
    id="reservation-qr-{{ $reservation->getKey() }}"
    class="shrink-0 rounded-xl border border-slate-200 bg-card p-2 text-center shadow-sm"
    wire:ignore
    x-data
    x-init="const renderQr = () => { window.QRCode?.toCanvas($refs.reservationQr, @js($payloadText), { width: 256, margin: 4, errorCorrectionLevel: 'L', color: { dark: '#29211D', light: '#FFF8EF' } }); }; window.QRCode ? renderQr() : window.addEventListener('qrcode:ready', renderQr, { once: true });"
>
    <canvas
        x-ref="reservationQr"
        width="256"
        height="256"
        class="size-48"
        aria-label="Código QR con los datos de la reservación"
    ></canvas>
    <span class="sr-only">Código QR de la reservación {{ $reservation->getKey() }}</span>
</div>
