import jsQR from 'jsqr';

export default function createQrScanner() {
    // Keep browser resources outside Alpine's reactive proxies.
    let stream = null;
    let barcodeDetector = null;
    let scanTimer = null;
    let canvas = null;
    let context = null;
    let session = 0;
    let onLeave = null;

    return {
        isOpen: false,
        cameraReady: false,
        status: '',
        result: '',
        isBookQr: false,
        isSubmittingBook: false,

        init() {
            onLeave = () => this.close();
            document.addEventListener('livewire:navigating', onLeave);
            window.addEventListener('pagehide', onLeave);
        },

        async open() {
            if (this.isOpen) {
                return;
            }

            this.stopCamera();
            const currentSession = session;
            this.isOpen = true;
            this.status = 'Solicitando acceso a la cámara…';
            this.result = '';
            this.isBookQr = false;
            this.isSubmittingBook = false;

            await this.$nextTick();

            if (!this.isOpen || currentSession !== session) {
                return;
            }

            if (!window.isSecureContext) {
                this.status = 'La cámara requiere una conexión HTTPS de confianza cuando accedes desde otro equipo.';

                return;
            }

            if (!navigator.mediaDevices?.getUserMedia) {
                this.status = 'Este navegador no permite acceder a la cámara.';

                return;
            }

            try {
                canvas = document.createElement('canvas');
                context = canvas.getContext('2d', { willReadFrequently: true });

                if (!context) {
                    this.status = 'No se pudo preparar el lector QR en este navegador.';

                    return;
                }

                const cameraStream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                    },
                });

                if (!this.isOpen || currentSession !== session) {
                    cameraStream.getTracks().forEach((track) => track.stop());

                    return;
                }

                stream = cameraStream;
                this.$refs.video.srcObject = cameraStream;
                await this.$refs.video.play();

                if (!this.isOpen || currentSession !== session) {
                    return;
                }

                const videoTrack = cameraStream.getVideoTracks?.()[0];

                try {
                    if (videoTrack?.getCapabilities?.().focusMode?.includes('continuous')) {
                        await videoTrack.applyConstraints({ advanced: [{ focusMode: 'continuous' }] });
                    }
                } catch {
                    // Some cameras expose focus capabilities without allowing them to be changed.
                }

                const Detector = window.BarcodeDetector;

                if (typeof Detector === 'function') {
                    try {
                        const formats = await Detector.getSupportedFormats?.();

                        if (!formats || formats.includes('qr_code')) {
                            barcodeDetector = new Detector({ formats: ['qr_code'] });
                        }
                    } catch {
                        barcodeDetector = null;
                    }
                }

                if (!this.isOpen || currentSession !== session) {
                    return;
                }

                this.cameraReady = true;
                this.status = 'Apunta la cámara al código QR.';
                void this.scan(currentSession);
            } catch (error) {
                if (currentSession !== session) {
                    return;
                }

                this.stopCamera();
                const messages = {
                    NotAllowedError: 'No se concedió permiso para usar la cámara. Permítelo en la configuración del sitio y vuelve a intentarlo.',
                    NotFoundError: 'No se encontró una cámara en este dispositivo.',
                    NotReadableError: 'No se pudo acceder a la cámara. Cierra otras aplicaciones que la estén usando y vuelve a intentarlo.',
                };
                this.status = messages[error?.name] || 'No se pudo iniciar la cámara. Revisa los permisos del navegador.';
            }
        },

        async reserveScannedBook() {
            if (!this.isBookQr || this.isSubmittingBook) {
                return;
            }

            const fields = new Map();
            for (const line of this.result.split(/\r?\n/)) {
                const separator = line.indexOf(':');

                if (separator > 0) {
                    fields.set(line.slice(0, separator).trim().toLowerCase(), line.slice(separator + 1).trim());
                }
            }

            const title = fields.get('titulo') || '';
            const isbn = fields.get('isbn') || '';

            if (!title) {
                this.status = 'El QR no incluye el título del libro.';
                return;
            }

            this.isSubmittingBook = true;
            this.status = 'Cargando el libro en Nuevo préstamo...';

            try {
                await this.$wire.selectBookFromQr(isbn, title);
                this.close();
            } catch {
                this.status = 'No se pudo cargar el libro. Inténtalo de nuevo.';
            } finally {
                this.isSubmittingBook = false;
            }
        },

        async scan(currentSession) {
            if (!this.isOpen || !this.cameraReady || currentSession !== session) {
                return;
            }

            const video = this.$refs.video;

            try {
                if (video.readyState >= 2 && video.videoWidth && video.videoHeight) {
                    let data = '';

                    if (barcodeDetector) {
                        try {
                            const detections = await barcodeDetector.detect(video);
                            data = detections.find((detection) => detection.format === 'qr_code')?.rawValue || '';
                        } catch {
                            barcodeDetector = null;
                        }
                    }

                    if (!this.isOpen || currentSession !== session) {
                        return;
                    }

                    if (!data) {
                        const scale = Math.min(1, 1600 / Math.max(video.videoWidth, video.videoHeight));
                        const width = Math.max(1, Math.round(video.videoWidth * scale));
                        const height = Math.max(1, Math.round(video.videoHeight * scale));

                        if (canvas.width !== width || canvas.height !== height) {
                            canvas.width = width;
                            canvas.height = height;
                        }

                        context.drawImage(video, 0, 0, width, height);
                        const frame = context.getImageData(0, 0, width, height);
                        data = jsQR(frame.data, width, height, { inversionAttempts: 'attemptBoth' })?.data || '';
                    }

                    if (data) {
                        this.result = data;
                        this.isBookQr = data.split(/\r?\n/).some((line) => line.trim() === 'Ficha bibliografica');
                        this.status = 'QR detectado correctamente.';
                        this.stopCamera();

                        return;
                    }
                }
            } catch {
                this.stopCamera();
                this.status = 'No se pudo leer la imagen de la cámara. Cierra el lector e inténtalo de nuevo.';

                return;
            }

            scanTimer = window.setTimeout(() => void this.scan(currentSession), 150);
        },

        stopCamera() {
            session++;
            window.clearTimeout(scanTimer);
            scanTimer = null;
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
            barcodeDetector = null;
            canvas = null;
            context = null;
            this.cameraReady = false;

            if (this.$refs.video) {
                this.$refs.video.srcObject = null;
            }
        },

        close() {
            this.isOpen = false;
            this.result = '';
            this.isBookQr = false;
            this.isSubmittingBook = false;
            this.status = '';
            this.stopCamera();
        },

        destroy() {
            this.close();
            document.removeEventListener('livewire:navigating', onLeave);
            window.removeEventListener('pagehide', onLeave);
        },
    };
}
