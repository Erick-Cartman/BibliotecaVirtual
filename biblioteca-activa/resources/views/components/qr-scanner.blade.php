<div x-data="qrScanner()">
    <button type="button" class="btn-primary cursor-pointer" x-on:click="open()">
        <svg viewBox="0 0 24 24" class="mr-2 size-5 fill-none stroke-current" stroke-width="1.8" aria-hidden="true">
            <path d="M4 7V5a1 1 0 0 1 1-1h2M17 4h2a1 1 0 0 1 1 1v2M20 17v2a1 1 0 0 1-1 1h-2M7 20H5a1 1 0 0 1-1-1v-2" />
            <path d="M8 8h3v3H8zM13 13h3v3h-3zM13 8h.01M8 16h.01M16 8h.01M8 13h.01" />
        </svg>
        Escanea este QR
    </button>

    <div
        id="qr-scanner-modal"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm sm:p-6"
        x-show="isOpen"
        x-cloak
        x-on:keydown.escape.window="close()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qr-scanner-title"
    >
        <button type="button" class="absolute inset-0 cursor-default" x-on:click="close()" aria-label="Cerrar escáner QR"></button>
        <section class="relative max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-3xl bg-card p-6 shadow-2xl sm:p-8">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <p class="eyebrow">Lectura rápida</p>
                    <h2 id="qr-scanner-title" class="mt-1 font-display text-3xl font-bold text-ink">Escanea este QR</h2>
                    <p class="mt-2 text-sm text-slate-600">Permite el acceso a la cámara y apunta al código QR.</p>
                </div>
                <button type="button" class="grid min-h-11 min-w-11 cursor-pointer place-items-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" x-on:click="close()" aria-label="Cerrar escáner">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
            </header>

            <div class="relative mt-6 overflow-hidden rounded-2xl bg-slate-950">
                <video x-ref="video" class="aspect-video w-full object-cover" autoplay playsinline muted></video>
                <div x-show="!cameraReady" class="absolute inset-0 grid place-items-center bg-slate-950/80 p-6 text-center text-white">
                    <p class="max-w-sm text-sm font-medium" x-text="status || 'Preparando la cámara…'"></p>
                </div>
                <div x-show="cameraReady" class="pointer-events-none absolute inset-0 grid place-items-center">
                    <div class="size-48 rounded-3xl border-2 border-white/90 shadow-[0_0_0_999px_rgba(15,23,42,0.28)] sm:size-60"></div>
                </div>
            </div>

            <p class="mt-4 text-sm font-medium text-slate-600" role="status" aria-live="polite" x-text="status"></p>

            <div x-show="result" x-cloak class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Contenido detectado</p>
                <pre class="mt-2 max-h-48 overflow-auto whitespace-pre-wrap break-words font-sans text-sm text-emerald-950" x-text="result"></pre>
            </div>

            <button type="button" class="btn-secondary mt-6 w-full cursor-pointer" x-on:click="close()">Cerrar</button>
        </section>
    </div>
</div>
