/**
 * Keeps a booking form's entered details across a page reload (used by the
 * "Try again" button when Google location search is unavailable). Stored in
 * sessionStorage for this tab only and removed as soon as it is restored.
 * Storage may be blocked (private mode), so every access is guarded.
 */

const PREFIX = 'h2cabs:draft:';
const SKIP = new Set(['_token']);

// The page path plus the form's action identifies the form (one per page).
const keyFor = (form) => `${PREFIX}${window.location.pathname}:${form.getAttribute('action')}`;

export function saveDraft(form) {
    const entries = [];

    // form.elements includes disabled fields (FormData would skip them).
    [...form.elements].forEach((element) => {
        if (!element.name || SKIP.has(element.name) || ['submit', 'button', 'file'].includes(element.type)) {
            return;
        }
        if (['radio', 'checkbox'].includes(element.type)) {
            if (element.checked) {
                entries.push([element.name, element.value, true]);
            }
            return;
        }
        entries.push([element.name, element.value, false]);
    });

    try {
        window.sessionStorage.setItem(keyFor(form), JSON.stringify(entries));
        return true;
    } catch {
        return false;
    }
}

export function restoreDraft(form) {
    let entries;

    try {
        const key = keyFor(form);
        entries = JSON.parse(window.sessionStorage.getItem(key) ?? 'null');
        window.sessionStorage.removeItem(key);
    } catch {
        return;
    }

    if (!Array.isArray(entries)) {
        return;
    }

    // Clear checkable groups first so the saved choice wins over defaults.
    const checkable = new Set(entries.filter(([, , checked]) => checked).map(([name]) => name));
    [...form.elements].forEach((element) => {
        if (checkable.has(element.name) && ['radio', 'checkbox'].includes(element.type)) {
            element.checked = false;
        }
    });

    entries.forEach(([name, value, checked]) => {
        const fields = [...form.elements].filter((element) => element.name === name);
        if (checked) {
            fields.filter((field) => field.value === value).forEach((field) => {
                field.checked = true;
            });
        } else if (fields[0] && typeof value === 'string') {
            fields[0].value = value;
        }
    });
}
