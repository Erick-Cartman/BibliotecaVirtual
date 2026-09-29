const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-book-viewer]').forEach((viewer) => {
    const book = viewer.querySelector('[data-book-3d]');
    const toggle = viewer.querySelector('[data-book-toggle]');

    if (!book || !toggle) {
        return;
    }

    let manuallyPaused = reducedMotionQuery.matches;
    let pausedByFocus = false;

    const updateBookState = () => {
        const reducedMotion = reducedMotionQuery.matches;
        const paused = reducedMotion || manuallyPaused || pausedByFocus;

        book.classList.toggle('is-paused', paused);
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

    if (typeof reducedMotionQuery.addEventListener === 'function') {
        reducedMotionQuery.addEventListener('change', handleMotionPreferenceChange);
    } else {
        reducedMotionQuery.addListener(handleMotionPreferenceChange);
    }

    updateBookState();
});
