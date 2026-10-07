import jsQR from 'jsqr';

export default function createQrScanner() {
    // Keep browser resources outside Alpine's reactive proxies.
    let stream = null;
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
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
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

                this.cameraReady = true;
                this.status = 'Apunta la cámara al código QR.';
                this.scan(currentSession);
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

        scan(currentSession) {
            if (!this.isOpen || !this.cameraReady || currentSession !== session) {
                return;
            }

            const video = this.$refs.video;

            try {
                if (video.readyState >= 2 && video.videoWidth && video.videoHeight) {
                    const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
                    const width = Math.max(1, Math.round(video.videoWidth * scale));
                    const height = Math.max(1, Math.round(video.videoHeight * scale));

                    if (canvas.width !== width || canvas.height !== height) {
                        canvas.width = width;
                        canvas.height = height;
                    }

                    context.drawImage(video, 0, 0, width, height);
                    const frame = context.getImageData(0, 0, width, height);
                    const code = jsQR(frame.data, width, height, { inversionAttempts: 'attemptBoth' });

                    if (code?.data) {
                        this.result = code.data;
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

            scanTimer = window.setTimeout(() => this.scan(currentSession), 150);
        },

        stopCamera() {
            session++;
            window.clearTimeout(scanTimer);
            scanTimer = null;
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
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
