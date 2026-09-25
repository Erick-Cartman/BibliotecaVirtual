import { gsap } from 'gsap';

const bookCarousels = document.querySelectorAll('[data-book-carousel]');
const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

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

    const updateCarousel = ({ animate = true } = {}) => {
        const maxIndex = Math.max(0, slides.length - visibleSlides);

        currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));
        const finalX = -currentIndex * getStep();

        gsap.killTweensOf(track);

        if (!animate || reducedMotionQuery.matches) {
            gsap.set(track, { x: finalX });
        } else {
            gsap.to(track, {
                x: finalX,
                duration: 0.55,
                ease: 'power3.out',
                overwrite: 'auto',
            });
        }

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
        updateCarousel({ animate: false });
    };

    const revealBooks = () => {
        if (reducedMotionQuery.matches) {
            gsap.set(slides, { clearProps: 'opacity,transform,visibility' });

            return;
        }

        gsap.fromTo(
            slides,
            { autoAlpha: 0, y: 16 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.48,
                ease: 'power2.out',
                stagger: 0.04,
                clearProps: 'opacity,transform,visibility',
            },
        );
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
    revealBooks();
});
