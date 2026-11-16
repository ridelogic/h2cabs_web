/**
 * Admin shell: mobile drawer, desktop sidebar collapse (remembered per
 * browser), and the top-bar menus (notifications, account): one open at a
 * time, closed on outside click / Escape.
 */
const STORAGE_KEY = 'h2c-admin-sidebar';

/** URL slug: lowercase words joined by single hyphens (mirrors Str::slug on the server). */
const slugify = (text) =>
    text
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/&/g, ' and ')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 80)
        .replace(/-+$/, '');

/**
 * Website Pages form: fill the slug from the title until the admin edits
 * the slug by hand (existing pages keep their slug so links do not break).
 */
function initSlugField(shell) {
    const source = shell.querySelector('[data-slug-source]');
    const target = shell.querySelector('[data-slug-target]');
    const preview = shell.querySelector('[data-slug-preview]');
    if (!source || !target) {
        return;
    }

    let auto = target.dataset.slugAuto === 'true' && target.value === '';
    const showPreview = () => preview && (preview.textContent = target.value || 'your-page');

    source.addEventListener('input', () => {
        if (auto) {
            target.value = slugify(source.value);
            showPreview();
        }
    });
    target.addEventListener('input', () => {
        auto = target.value === '';
        showPreview();
    });
    target.addEventListener('change', () => {
        target.value = slugify(target.value);
        showPreview();
    });
}

/** Fetch the latest new booking requests into the bell menu (fresh on every open). */
async function loadNotifications(container) {
    if (!container || container.dataset.loading === 'true') {
        return;
    }
    container.dataset.loading = 'true';
    container.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(container.dataset.src, {
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        // A redirect means the session expired (sent to the login page).
        if (!response.ok || response.redirected) {
            throw new Error(`HTTP ${response.status}`);
        }
        container.innerHTML = await response.text();
    } catch {
        container.innerHTML = '<p class="px-4 py-8 text-center text-sm text-slate-500">Could not load notifications. Use the link below instead.</p>';
    } finally {
        container.dataset.loading = 'false';
        container.removeAttribute('aria-busy');
    }
}

export function initAdminShell() {
    const shell = document.querySelector('[data-admin-shell]');
    if (!shell) {
        return;
    }

    const root = document.documentElement;
    const backdrop = shell.querySelector('[data-sidebar-backdrop]');
    const openButton = shell.querySelector('[data-sidebar-open]');
    const collapseButton = shell.querySelector('[data-sidebar-collapse]');
    const menus = [...shell.querySelectorAll('[data-admin-menu]')];

    // --- Mobile drawer --------------------------------------------------------
    const setDrawer = (open) => {
        shell.dataset.sidebarOpen = String(open);
        backdrop.hidden = !open;
        openButton?.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) {
            shell.querySelector('#admin-sidebar a, #admin-sidebar button')?.focus();
        }
    };

    openButton?.addEventListener('click', () => setDrawer(true));
    shell.querySelector('[data-sidebar-close]')?.addEventListener('click', () => {
        setDrawer(false);
        openButton?.focus();
    });
    backdrop?.addEventListener('click', () => setDrawer(false));
    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches) {
            setDrawer(false);
        }
    });

    // --- Desktop collapse ------------------------------------------------------
    const syncCollapse = () => {
        const collapsed = root.dataset.sidebar === 'collapsed';
        collapseButton?.setAttribute('aria-expanded', String(!collapsed));
        collapseButton?.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    };

    collapseButton?.addEventListener('click', () => {
        const collapsed = root.dataset.sidebar !== 'collapsed';
        root.dataset.sidebar = collapsed ? 'collapsed' : 'expanded';
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? 'collapsed' : 'expanded');
        } catch {
            // Storage unavailable (private mode): the choice lasts for this page only.
        }
        syncCollapse();
    });
    syncCollapse();

    initSlugField(shell);

    // --- Top-bar menus -----------------------------------------------------------
    menus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (menu.open) {
                menus.filter((other) => other !== menu).forEach((other) => (other.open = false));
                loadNotifications(menu.querySelector('[data-notifications]'));
            }
        });
    });

    document.addEventListener('click', (event) => {
        menus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        if (shell.dataset.sidebarOpen === 'true') {
            setDrawer(false);
            openButton?.focus();
        }
        const open = menus.find((menu) => menu.open);
        if (open) {
            open.open = false;
            open.querySelector('summary').focus();
        }
    });
}
