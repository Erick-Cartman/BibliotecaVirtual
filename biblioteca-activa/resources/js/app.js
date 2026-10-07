import QRCode from 'qrcode';

window.QRCode = QRCode;
window.dispatchEvent(new CustomEvent('qrcode:ready'));

window.qrScanner = () => ({
    isOpen: false,
    cameraReady: false,
    status: '',
    result: '',
    stream: null,
    detector: null,
    animationFrame: null,
    detecting: false,

    async open() {
        this.isOpen = true;
        this.cameraReady = false;
        this.status = 'Solicitando acceso a la cámara…';
        this.result = '';

        await this.$nextTick();

        const isLocalhost = ['localhost', '127.0.0.1', '[::1]'].includes(window.location.hostname);
        if (!window.isSecureContext && !isLocalhost) {
            this.status = 'La cámara requiere una conexión HTTPS cuando accedes desde otro equipo.';

            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            this.status = 'Este navegador no permite acceder a la cámara.';

            return;
        }

        if (!('BarcodeDetector' in window)) {
            this.status = 'Este navegador no incluye lector QR. Prueba con una versión reciente de Chrome o Edge.';

            return;
        }

        try {
            const supportedFormats = await window.BarcodeDetector.getSupportedFormats?.();

            if (supportedFormats && !supportedFormats.includes('qr_code')) {
                this.status = 'Este navegador no puede leer códigos QR.';

                return;
            }

            this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            });

            if (!this.isOpen) {
                stream.getTracks().forEach((track) => track.stop());

                return;
            }

            this.stream = stream;
            this.$refs.video.srcObject = stream;
            await this.$refs.video.play();
            this.cameraReady = true;
            this.status = 'Apunta la cámara al código QR.';
            this.scan();
        } catch (error) {
            this.stopCamera();
            this.status = error?.name === 'NotAllowedError'
                ? 'No se concedió permiso para usar la cámara.'
                : 'No se pudo iniciar la cámara. Revisa los permisos del navegador.';
        }
    },

    async scan() {
        if (!this.isOpen || !this.cameraReady || !this.detector || this.detecting) {
            return;
        }

        this.detecting = true;

        try {
            const detectedCodes = await this.detector.detect(this.$refs.video);
            const detectedCode = detectedCodes.find((code) => code.rawValue);

            if (detectedCode) {
                this.result = detectedCode.rawValue;
                this.status = 'QR detectado correctamente.';
                this.stopCamera();

                return;
            }
        } catch (error) {
            this.status = 'Buscando un código QR…';
        } finally {
            this.detecting = false;
        }

        if (this.isOpen && this.cameraReady) {
            this.animationFrame = window.requestAnimationFrame(() => this.scan());
        }
    },

    stopCamera() {
        if (this.animationFrame) {
            window.cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }

        this.stream?.getTracks().forEach((track) => track.stop());
        this.stream = null;
        this.cameraReady = false;

        if (this.$refs.video) {
            this.$refs.video.srcObject = null;
        }
    },

    close() {
        this.isOpen = false;
        this.result = '';
        this.status = '';
        this.stopCamera();
    },
});

const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-book-viewer]').forEach((viewer) => {
    const book = viewer.querySelector('[data-book-3d]');

    if (!book) {
        return;
    }

    let pausedByFocus = false;
    let pausedByVisibility = document.hidden;
    let pausedByViewport = false;

    const isPaused = () => reducedMotionQuery.matches
        || pausedByFocus
        || pausedByVisibility
        || pausedByViewport;

    const updateBookState = () => {
        const reducedMotion = reducedMotionQuery.matches;
        const paused = isPaused();

        book.classList.toggle('is-rotating', !paused);
    };

    viewer.addEventListener('focusin', () => {
        if (!reducedMotionQuery.matches) {
            pausedByFocus = true;
            updateBookState();
        }
    });

    viewer.addEventListener('focusout', (event) => {
        if (!viewer.contains(event.relatedTarget)) {
            pausedByFocus = false;
            updateBookState();
        }
    });

    const handleMotionPreferenceChange = () => updateBookState();

    const handleVisibilityChange = () => {
        pausedByVisibility = document.hidden;
        updateBookState();
    };

    const viewportObserver = typeof IntersectionObserver === 'function'
        ? new IntersectionObserver(([entry]) => {
            pausedByViewport = !entry.isIntersecting;
            updateBookState();
        }, { threshold: 0.1 })
        : null;

    viewportObserver?.observe(viewer);
    document.addEventListener('visibilitychange', handleVisibilityChange);

    if (typeof reducedMotionQuery.addEventListener === 'function') {
        reducedMotionQuery.addEventListener('change', handleMotionPreferenceChange);
    } else {
        reducedMotionQuery.addListener(handleMotionPreferenceChange);
    }

    updateBookState();
});
