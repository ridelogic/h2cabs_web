/**
 * Homepage hero slider: previous/next buttons and arrow keys switch slides.
 * Inactive slides are hidden from assistive technology (aria-hidden + inert).
 * No autoplay, so the content never moves on its own.
 */
export function initHeroSlider() {
    const root = document.querySelector('[data-hero-slider]');
    if (!root) {
        return;
    }

    const slides = [...root.querySelectorAll('[data-hero-slide]')];
    const prev = root.querySelector('[data-hero-prev]');
    const next = root.querySelector('[data-hero-next]');
    const status = root.querySelector('[data-hero-status]');
    if (slides.length < 2 || !prev || !next) {
        return;
    }

    let current = Math.max(0, slides.findIndex((slide) => slide.dataset.active === 'true'));

    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            const active = i === current;
            slide.dataset.active = String(active);
            slide.toggleAttribute('inert', !active);
            if (active) {
                slide.removeAttribute('aria-hidden');
            } else {
                slide.setAttribute('aria-hidden', 'true');
            }
        });
        if (status) {
            status.textContent = `Slide ${current + 1} of ${slides.length}`;
        }
    };

    prev.hidden = false;
    next.hidden = false;
    prev.addEventListener('click', () => show(current - 1));
    next.addEventListener('click', () => show(current + 1));

    root.addEventListener('keydown', (event) => {
        if (event.target.closest('input, textarea, select')) {
            return;
        }
        if (event.key === 'ArrowLeft') {
            show(current - 1);
        } else if (event.key === 'ArrowRight') {
            show(current + 1);
        }
    });
}
