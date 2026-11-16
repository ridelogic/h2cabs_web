/**
 * Public site behaviour: mobile navigation and header scroll state.
 * Progressive enhancement only — the page works without JavaScript.
 */

function initMobileNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const menu = document.getElementById(toggle?.getAttribute('aria-controls') ?? '');

    if (!toggle || !menu) {
        return;
    }

    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        menu.hidden = !open;
        document.body.classList.toggle('overflow-hidden', open);
    };

    toggle.addEventListener('click', () => {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    menu.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
        }
    });

    // Close the mobile menu if the viewport grows to desktop width.
    window.matchMedia('(min-width: 1280px)').addEventListener('change', (event) => {
        if (event.matches) {
            setOpen(false);
        }
    });
}

function initHeaderScroll() {
    const header = document.querySelector('[data-site-header]');

    if (!header) {
        return;
    }

    const update = () => {
        header.dataset.scrolled = String(window.scrollY > 8);
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
}

initMobileNav();
initHeaderScroll();
