import assert from 'node:assert/strict';
import test from 'node:test';
import QRCode from 'qrcode';
import createQrScanner from '../../resources/js/qr-scanner.js';

function qrFrame(text) {
    const { modules } = QRCode.create(text, { errorCorrectionLevel: 'M' });
    const margin = 4;
    const scale = 5;
    const size = (modules.size + margin * 2) * scale;
    const data = new Uint8ClampedArray(size * size * 4);

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const row = Math.floor(y / scale) - margin;
            const column = Math.floor(x / scale) - margin;
            const dark = row >= 0 && column >= 0 && row < modules.size && column < modules.size
                && modules.data[row * modules.size + column];
            data.set(dark ? [41, 33, 29, 255] : [255, 248, 239, 255], (y * size + x) * 4);
        }
    }

    return { data, width: size, height: size };
}

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((accept, fail) => { resolve = accept; reject = fail; });

    return { promise, resolve, reject };
}

function setup(t, { frame = null, getUserMedia = null, secure = true } = {}) {
    const tracks = [{ stop: t.mock.fn() }];
    const stream = { getTracks: () => tracks };
    const timers = new Map();
    let timerId = 0;
    const browser = Object.assign(new EventTarget(), {
        isSecureContext: secure,
        setTimeout: (callback) => { timers.set(++timerId, callback); return timerId; },
        clearTimeout: (id) => timers.delete(id),
    });
    const video = {
        readyState: frame ? 2 : 0,
        videoWidth: frame?.width || 0,
        videoHeight: frame?.height || 0,
        srcObject: null,
        play: t.mock.fn(async () => {}),
    };
    const context = {
        drawImage: t.mock.fn(),
        getImageData: t.mock.fn(() => frame),
    };
    const canvas = { width: 0, height: 0, getContext: () => context };
    const document = Object.assign(new EventTarget(), { createElement: () => canvas });
    const mediaDevices = { getUserMedia: t.mock.fn(getUserMedia || (async () => stream)) };
    const globals = { window: browser, document, navigator: { mediaDevices } };
    const originals = Object.fromEntries(Object.keys(globals).map((key) => [key, Object.getOwnPropertyDescriptor(globalThis, key)]));

    for (const [key, value] of Object.entries(globals)) {
        Object.defineProperty(globalThis, key, { configurable: true, value });
    }

    const scanner = createQrScanner();
    scanner.$refs = { video };
    scanner.$nextTick = async () => {};
    scanner.init();

    t.after(() => {
        scanner.destroy();
        for (const [key, descriptor] of Object.entries(originals)) {
            if (descriptor) {
                Object.defineProperty(globalThis, key, descriptor);
            } else {
                delete globalThis[key];
            }
        }
    });

    return { scanner, video, tracks, stream, timers, browser, document, mediaDevices, context };
}

const payloads = [
    ['book', 'Biblioteca Activa\nFicha bibliografica\nTitulo: Diseño de sistemas\nAutor(es): Elena Torres\nISBN: 9781234567897\nEditorial: Editorial Académica\nAnio de edicion: 2024\nClasificacion: 005.12\nUnidad de aprendizaje: Ingeniería de software\nPrograma: Ingeniería\nDisponibilidad: 2 de 3'],
    ['reservation', 'Biblioteca Activa\nReservacion: 507f1f77bcf86cd799439011\nAlumno: María Pérez\nCodigo de alumno: EST-001\nLibro: Historia de México\nEstado: Pendiente\nFecha: 06/10/2026 12:00'],
];

for (const [kind, payload] of payloads) {
    test(`reads ${kind} QR pixels without BarcodeDetector and releases the camera`, async (t) => {
        const { scanner, video, tracks, timers, browser, mediaDevices } = setup(t, { frame: qrFrame(payload) });
        assert.equal('BarcodeDetector' in browser, false);

        await scanner.open();

        assert.equal(scanner.result, payload);
        assert.equal(scanner.status, 'QR detectado correctamente.');
        assert.equal(scanner.cameraReady, false);
        assert.equal(video.srcObject, null);
        assert.equal(tracks[0].stop.mock.callCount(), 1);
        assert.equal(timers.size, 0);
        assert.equal(mediaDevices.getUserMedia.mock.calls[0].arguments[0].video.facingMode.ideal, 'environment');
        assert.equal(mediaDevices.getUserMedia.mock.calls[0].arguments[0].audio, false);
    });
}

test('continues scanning a blank frame and reads a QR in a later frame', async (t) => {
    const payload = 'Biblioteca Activa\nLibro: Álgebra';
    const frame = qrFrame(payload);
    const blank = { ...frame, data: new Uint8ClampedArray(frame.data.length).fill(255) };
    const { scanner, context, timers } = setup(t, { frame: blank });
    await scanner.open();
    assert.equal(scanner.result, '');
    assert.equal(scanner.cameraReady, true);
    assert.equal(timers.size, 1);
    context.getImageData = () => frame;
    const [id, nextFrame] = [...timers][0];
    timers.delete(id);

    nextFrame();

    assert.equal(scanner.result, payload);
    assert.equal(timers.size, 0);
});

test('waits for video frames and stops the pending loop on close', async (t) => {
    const { scanner, tracks, context, timers } = setup(t);
    await scanner.open();
    assert.equal(context.drawImage.mock.callCount(), 0);
    assert.equal(timers.size, 1);

    scanner.close();

    assert.equal(scanner.isOpen, false);
    assert.equal(tracks[0].stop.mock.callCount(), 1);
    assert.equal(timers.size, 0);
});

test('explains denied camera permission and allows reopening after permission is granted', async (t) => {
    const { scanner, mediaDevices, stream } = setup(t, {
        getUserMedia: async () => { throw new DOMException('Denied', 'NotAllowedError'); },
    });

    await scanner.open();

    assert.match(scanner.status, /No se concedió permiso/);
    assert.equal(scanner.cameraReady, false);
    scanner.close();
    mediaDevices.getUserMedia = async () => stream;
    await scanner.open();
    assert.equal(scanner.cameraReady, true);
});

test('does not request the camera on an insecure connection', async (t) => {
    const { scanner, mediaDevices } = setup(t, { secure: false });

    await scanner.open();

    assert.match(scanner.status, /HTTPS/);
    assert.equal(mediaDevices.getUserMedia.mock.callCount(), 0);
});

test('releases permission granted after the scanner has been closed', async (t) => {
    const permission = deferred();
    const { scanner, tracks, stream, video } = setup(t, { getUserMedia: () => permission.promise });
    const opening = scanner.open();
    await Promise.resolve();
    scanner.close();

    permission.resolve(stream);
    await opening;

    assert.equal(tracks[0].stop.mock.callCount(), 1);
    assert.equal(video.srcObject, null);
    assert.equal(scanner.cameraReady, false);
});

test('an old camera request cannot replace a reopened scanner session', async (t) => {
    const permission = deferred();
    const oldTrack = { stop: t.mock.fn() };
    const { scanner, mediaDevices, stream, video, tracks } = setup(t, { getUserMedia: () => permission.promise });
    const opening = scanner.open();
    await Promise.resolve();
    scanner.close();
    mediaDevices.getUserMedia = async () => stream;
    await scanner.open();

    permission.resolve({ getTracks: () => [oldTrack] });
    await opening;

    assert.equal(oldTrack.stop.mock.callCount(), 1);
    assert.equal(tracks[0].stop.mock.callCount(), 0);
    assert.equal(video.srcObject, stream);
    assert.equal(scanner.cameraReady, true);
});

test('releases the stream when video playback fails', async (t) => {
    const { scanner, video, tracks } = setup(t);
    video.play = async () => { throw new Error('Playback failed'); };

    await scanner.open();

    assert.equal(tracks[0].stop.mock.callCount(), 1);
    assert.equal(video.srcObject, null);
    assert.equal(scanner.cameraReady, false);
    assert.match(scanner.status, /No se pudo iniciar la cámara/);
});

for (const leave of ['navigation', 'pagehide', 'destroy']) {
    test(`stops the camera on ${leave}`, async (t) => {
        const { scanner, browser, document, tracks, timers } = setup(t);
        await scanner.open();

        if (leave === 'navigation') {
            document.dispatchEvent(new Event('livewire:navigating'));
        } else if (leave === 'pagehide') {
            browser.dispatchEvent(new Event('pagehide'));
        } else {
            scanner.destroy();
        }

        assert.equal(scanner.isOpen, false);
        assert.equal(tracks[0].stop.mock.callCount(), 1);
        assert.equal(timers.size, 0);
    });
}
