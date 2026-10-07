import QRCode from 'qrcode';
import createQrScanner from './qr-scanner.js';

window.QRCode = QRCode;
window.dispatchEvent(new CustomEvent('qrcode:ready'));

window.qrScanner = createQrScanner;

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
