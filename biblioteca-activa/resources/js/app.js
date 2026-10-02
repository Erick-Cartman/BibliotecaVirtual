import { gsap } from 'gsap';
import QRCode from 'qrcode';

window.QRCode = QRCode;
window.dispatchEvent(new CustomEvent('qrcode:ready'));

const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-book-viewer]').forEach((viewer) => {
    const book = viewer.querySelector('[data-book-3d]');
    const toggle = viewer.querySelector('[data-book-toggle]');

    if (!book || !toggle) {
        return;
    }

    const rotation = gsap.to(book, {
        rotationY: 360,
        rotationX: -4,
        duration: 20,
        ease: 'none',
        repeat: -1,
        paused: true,
        transformOrigin: '50% 50%',
        transformPerspective: 1200,
    });

    let manuallyPaused = false;
    let pausedByFocus = false;
    let pausedByVisibility = document.hidden;
    let pausedByViewport = false;

    gsap.set(book, {
        rotationY: 0,
        rotationX: -4,
        transformOrigin: '50% 50%',
        transformPerspective: 1200,
    });

    const isPaused = () => reducedMotionQuery.matches
        || manuallyPaused
        || pausedByFocus
        || pausedByVisibility
        || pausedByViewport;

    const updateBookState = () => {
        const reducedMotion = reducedMotionQuery.matches;
        const paused = isPaused();

        if (paused) {
            rotation.pause();
        } else {
            rotation.resume();
        }

        toggle.disabled = reducedMotion;
        toggle.setAttribute('aria-pressed', String(manuallyPaused));
        toggle.setAttribute(
            'aria-label',
            reducedMotion
                ? 'La rotación está pausada por tu preferencia de movimiento'
                : manuallyPaused
                    ? 'Reanudar la rotación 3D'
                    : 'Pausar la rotación 3D',
        );
        toggle.textContent = reducedMotion ? 'Rotación pausada' : manuallyPaused ? 'Reanudar giro' : 'Pausar giro';
    };

    toggle.addEventListener('click', () => {
        manuallyPaused = !manuallyPaused;
        pausedByFocus = false;
        updateBookState();
    });

    viewer.addEventListener('focusin', () => {
        if (!manuallyPaused && !reducedMotionQuery.matches) {
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
