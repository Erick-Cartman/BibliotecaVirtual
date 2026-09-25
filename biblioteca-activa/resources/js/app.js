const bookCarousels = document.querySelectorAll('[data-book-carousel]');

bookCarousels.forEach((carousel) => {
    const viewport = carousel.querySelector('[data-book-viewport]');
    const track = carousel.querySelector('[data-book-track]');
    const slides = [...carousel.querySelectorAll('[data-book-slide]')];
    const previousButton = carousel.querySelector('[data-book-carousel-prev]');
    const nextButton = carousel.querySelector('[data-book-carousel-next]');
    const status = carousel.querySelector('[data-book-carousel-status]');

    if (!viewport || !track || slides.length === 0 || !previousButton || !nextButton) {
        return;
    }

    let currentIndex = 0;
    let visibleSlides = 1;

    const getStep = () => {
        const gap = Number.parseFloat(getComputedStyle(track).columnGap) || 0;

        return slides[0].getBoundingClientRect().width + gap;
    };

    const updateCarousel = () => {
        const maxIndex = Math.max(0, slides.length - visibleSlides);

        currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));
        track.style.transform = `translate3d(-${currentIndex * getStep()}px, 0, 0)`;
        previousButton.disabled = currentIndex === 0;
        nextButton.disabled = currentIndex === maxIndex;

        if (status) {
            const firstVisible = currentIndex + 1;
            const lastVisible = Math.min(currentIndex + visibleSlides, slides.length);

            status.textContent = `Libros ${firstVisible}–${lastVisible} de ${slides.length}`;
        }
    };

    const measureCarousel = () => {
        visibleSlides = Math.max(1, Math.floor(viewport.clientWidth / getStep()));
        updateCarousel();
    };

    previousButton.addEventListener('click', () => {
        currentIndex -= 1;
        updateCarousel();
    });

    nextButton.addEventListener('click', () => {
        currentIndex += 1;
        updateCarousel();
    });

    viewport.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            currentIndex -= 1;
            updateCarousel();
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            currentIndex += 1;
            updateCarousel();
        }
    });

    if ('ResizeObserver' in window) {
        new ResizeObserver(measureCarousel).observe(viewport);
    } else {
        window.addEventListener('resize', measureCarousel, { passive: true });
    }

    measureCarousel();
});
