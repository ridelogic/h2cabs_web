/**
 * Location autocomplete for [data-place-field] (ARIA 1.2 combobox pattern).
 *
 * Two real providers, chosen by the server (data-place-mode):
 *  - "google": Places API (New) through the Maps JavaScript API in the
 *    browser — google.maps.places.AutocompleteSuggestion with session
 *    tokens, then Place.fetchFields() for the chosen suggestion. The key is
 *    a referrer-restricted browser key loaded by <x-booking.maps-loader>.
 *  - "proxy":  the same Places API called by our server (/places/search),
 *    for sites that only have a server key.
 *
 * Locations must be chosen from Google suggestions (the server requires
 * the place ID). There is no manual-entry fallback: the fields are rendered
 * disabled and are enabled only once Google search is genuinely ready. If
 * Google is missing, fails to load, rejects the key or a request errors,
 * every location field in the form is disabled again and a message with a
 * "Try again" button is shown. Other entered details are kept (see
 * form-draft.js). Pickup and destination use separate session tokens.
 */

import { saveDraft } from './form-draft';

const DEBOUNCE_MS = 300;

// Bias results towards Puducherry; restrict to India.
const BIAS = { center: { lat: 11.9416, lng: 79.8083 }, radius: 50000 };

/** How long to wait for the Maps JavaScript API before giving up. */
const LOAD_TIMEOUT_MS = 10000;

export const UNAVAILABLE_MESSAGE = 'Google location search is unavailable. Please try again later.';

const MESSAGES = {
    starting: 'Loading Google location search…',
    idle: 'Start typing, then choose your location from the Google suggestions.',
    loading: 'Searching…',
    empty: 'No matching places found. Try a different spelling or a nearby landmark.',
    busy: 'Too many searches right now. Please wait a moment, then keep typing.',
    choose: 'Please choose a location from the Google suggestions.',
};

class ProviderError extends Error {
    constructor(kind) {
        super(kind);
        this.kind = kind;
    }
}

function fallbackToken() {
    return window.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

/** Places API (New) via the Maps JavaScript API (browser key). */
class GooglePlacesProvider {
    constructor() {
        this.library = null;
        this.token = null;
    }

    /** Resolves once the Places library has loaded; rejects otherwise. */
    async ready() {
        if (window.__h2PlacesAuthFailed || !window.google?.maps?.importLibrary) {
            throw new ProviderError('unavailable');
        }

        let timeout;
        const timer = new Promise((_, reject) => {
            timeout = setTimeout(() => reject(new ProviderError('unavailable')), LOAD_TIMEOUT_MS);
        });

        try {
            const library = await Promise.race([window.google.maps.importLibrary('places'), timer]);
            if (!library?.AutocompleteSuggestion || window.__h2PlacesAuthFailed) {
                throw new ProviderError('unavailable');
            }
            this.library = library;
        } catch {
            throw new ProviderError('unavailable');
        } finally {
            clearTimeout(timeout);
        }
    }

    async places() {
        if (window.__h2PlacesAuthFailed || !this.library) {
            throw new ProviderError('unavailable');
        }
        return this.library;
    }

    async search(query) {
        let places;
        try {
            places = await this.places();
        } catch {
            throw new ProviderError('unavailable');
        }

        this.token ??= new places.AutocompleteSessionToken();

        try {
            const { suggestions } = await places.AutocompleteSuggestion.fetchAutocompleteSuggestions({
                input: query,
                sessionToken: this.token,
                includedRegionCodes: ['in'],
                locationBias: BIAS,
                language: 'en',
                region: 'in',
            });

            return suggestions
                .map((suggestion) => suggestion.placePrediction)
                .filter(Boolean)
                .map((prediction) => ({
                    id: prediction.placeId,
                    label: prediction.text?.text ?? '',
                    main: prediction.mainText?.text ?? prediction.text?.text ?? '',
                    secondary: prediction.secondaryText?.text ?? null,
                    prediction,
                }));
        } catch {
            throw new ProviderError('failed');
        }
    }

    /** Fetching place details ends the billing session. */
    async select(result) {
        try {
            const place = result.prediction.toPlace();
            await place.fetchFields({ fields: ['formattedAddress', 'displayName'] });
            const name = place.displayName ?? result.main;
            const address = place.formattedAddress ?? result.label;
            return { id: place.id ?? result.id, label: address.startsWith(name) ? address : `${name}, ${address}` };
        } catch {
            // The suggestion itself came from Google, so its ID and text are real.
            return { id: result.id, label: result.label };
        } finally {
            this.token = null;
        }
    }
}

/** Places API (New) through our server endpoint (server key). */
class ServerProxyProvider {
    constructor(endpoint) {
        this.endpoint = endpoint;
        this.token = fallbackToken();
        this.controller = null;
    }

    /** The server only uses this mode when a Places key is configured. */
    async ready() {}

    async search(query) {
        this.controller?.abort();
        this.controller = new AbortController();

        let response;
        try {
            response = await fetch(`${this.endpoint}?${new URLSearchParams({ q: query, session: this.token })}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
        } catch (error) {
            if (error.name === 'AbortError') {
                throw error;
            }
            throw new ProviderError('failed');
        }

        if (response.status === 503) {
            throw new ProviderError('unavailable');
        }
        if (response.status === 429) {
            throw new ProviderError('busy');
        }
        if (!response.ok) {
            throw new ProviderError('failed');
        }

        const data = await response.json();
        return Array.isArray(data.results) ? data.results : [];
    }

    async select(result) {
        this.token = fallbackToken();
        return { id: result.id, label: result.label };
    }
}

class PlaceAutocomplete {
    constructor(root, provider, onFailure) {
        this.root = root;
        this.provider = provider;
        this.onFailure = onFailure;
        this.input = root.querySelector('[data-place-input]');
        this.placeId = root.querySelector('[data-place-id]');
        this.popup = root.querySelector('[data-place-popup]');
        this.listbox = root.querySelector('[role="listbox"]');
        this.status = root.querySelector('[data-place-status]');
        this.spinner = root.querySelector('[data-place-spinner]');
        this.minLength = Number(root.dataset.minLength || 3);
        this.results = [];
        this.active = -1;
        this.timer = null;
        this.requestId = 0;
        this.enabled = false;

        this.input.addEventListener('input', () => this.onInput());
        this.input.addEventListener('keydown', (event) => this.onKeydown(event));
        this.input.addEventListener('blur', () => setTimeout(() => this.close(), 150));
        this.listbox?.addEventListener('mousedown', (event) => event.preventDefault());
        this.listbox?.addEventListener('click', (event) => {
            const option = event.target.closest('[role="option"]');
            if (option) {
                this.select(Number(option.dataset.index));
            }
        });
    }

    /** True when the field holds a place chosen from Google (or is empty). */
    isComplete() {
        return this.input.value.trim() === '' || this.placeId.value !== '';
    }

    onInput() {
        // Typed text no longer matches a previously chosen place.
        this.placeId.value = '';
        clearTimeout(this.timer);

        if (!this.enabled) {
            return;
        }

        const query = this.input.value.trim();

        if (query.length < this.minLength) {
            this.requestId += 1;
            this.close();
            this.setStatus(MESSAGES.idle);
            return;
        }

        this.timer = setTimeout(() => this.search(query), DEBOUNCE_MS);
    }

    async search(query) {
        const request = ++this.requestId;
        this.setLoading(true);
        this.setStatus(MESSAGES.loading);

        try {
            const results = await this.provider.search(query);

            if (request !== this.requestId || !this.enabled) {
                return; // A newer search has started, or search was disabled.
            }

            this.results = results;
            this.render();
        } catch (error) {
            if (error.name === 'AbortError' || request !== this.requestId) {
                return;
            }
            if (error.kind === 'busy') {
                this.close();
                this.setStatus(MESSAGES.busy);
            } else {
                // Missing key, denied key or a failed request: disable every
                // location field in the form. No fake suggestions are shown.
                this.onFailure();
            }
        } finally {
            if (request === this.requestId) {
                this.setLoading(false);
            }
        }
    }

    render() {
        this.active = -1;
        this.input.removeAttribute('aria-activedescendant');
        this.listbox.replaceChildren();

        if (this.results.length === 0) {
            this.close();
            this.setStatus(MESSAGES.empty);
            return;
        }

        this.results.forEach((result, index) => {
            const option = document.createElement('li');
            option.id = `${this.listbox.id}-${index}`;
            option.role = 'option';
            option.setAttribute('aria-selected', 'false');
            option.dataset.index = String(index);
            option.className = 'flex cursor-pointer items-start gap-3 rounded-xl px-3 py-2.5 aria-selected:bg-brand-blue-50 hover:bg-brand-blue-50';

            const text = document.createElement('span');
            text.className = 'min-w-0';

            const main = document.createElement('span');
            main.className = 'block font-semibold text-brand-blue-950';
            main.textContent = result.main || result.label;
            text.append(main);

            if (result.secondary) {
                const secondary = document.createElement('span');
                secondary.className = 'block text-sm text-muted';
                secondary.textContent = result.secondary;
                text.append(secondary);
            }

            option.append(text);
            this.listbox.append(option);
        });

        this.open();
        const count = this.results.length;
        this.setStatus(`${count} ${count === 1 ? 'suggestion' : 'suggestions'} available. Use the up and down arrows to choose.`);
    }

    onKeydown(event) {
        const open = !this.popup.hidden;

        if (event.key === 'ArrowDown' && this.results.length) {
            event.preventDefault();
            if (!open) {
                this.open();
            }
            this.highlight(Math.min(this.active + 1, this.results.length - 1));
        } else if (event.key === 'ArrowUp' && open) {
            event.preventDefault();
            this.highlight(Math.max(this.active - 1, 0));
        } else if (event.key === 'Enter' && open && this.active >= 0) {
            event.preventDefault();
            this.select(this.active);
        } else if (event.key === 'Escape' && open) {
            event.preventDefault();
            this.close();
        }
    }

    highlight(index) {
        this.active = index;
        [...this.listbox.children].forEach((option, i) => {
            option.setAttribute('aria-selected', String(i === index));
            if (i === index) {
                option.scrollIntoView({ block: 'nearest' });
                this.input.setAttribute('aria-activedescendant', option.id);
            }
        });
    }

    async select(index) {
        const result = this.results[index];

        if (!result) {
            return;
        }

        this.close();
        this.input.value = result.label;
        this.setLoading(true);

        const place = await this.provider.select(result);

        this.setLoading(false);
        this.input.value = place.label;
        this.placeId.value = place.id ?? '';
        this.input.removeAttribute('aria-invalid');
        this.setStatus(`Selected: ${place.label}`);
        this.input.dispatchEvent(new CustomEvent('place:selected', { bubbles: true, detail: place }));
    }

    open() {
        this.popup.hidden = false;
        this.input.setAttribute('aria-expanded', 'true');
    }

    close() {
        if (!this.popup) {
            return;
        }
        this.popup.hidden = true;
        this.input.setAttribute('aria-expanded', 'false');
        this.input.removeAttribute('aria-activedescendant');
        this.active = -1;
    }

    enable() {
        this.enabled = true;
        this.input.disabled = false;
        this.input.removeAttribute('aria-disabled');
        this.setStatus(this.placeId.value ? `Selected: ${this.input.value}` : MESSAGES.idle);
    }

    /** Disable the field; its text and place ID stay in place for later. */
    disable(message) {
        this.enabled = false;
        clearTimeout(this.timer);
        this.requestId += 1;
        this.setLoading(false);
        this.close();
        this.input.disabled = true;
        this.input.setAttribute('aria-disabled', 'true');
        this.setStatus(message);
    }

    /** Flag a typed location that was not chosen from the suggestions. */
    flagIncomplete() {
        this.input.setAttribute('aria-invalid', 'true');
        this.setStatus(MESSAGES.choose);
    }

    setLoading(loading) {
        if (this.spinner) {
            this.spinner.hidden = !loading;
        }
        this.root.setAttribute('aria-busy', String(loading));
    }

    setStatus(message) {
        if (this.status) {
            this.status.textContent = message;
        }
    }
}

/**
 * All location fields of one form share an availability state:
 * "starting" → "ready", or "unavailable" (missing key, load failure, denied
 * key, request error). The form is told through a "places:state" event so
 * it can block submission while locations cannot be chosen.
 */
class LocationSearch {
    constructor(scope, roots) {
        this.scope = scope;
        this.mode = roots[0].dataset.placeMode;
        this.notice = scope.querySelector('[data-places-notice]');
        this.state = 'starting';
        this.fields = roots.map((root) => {
            // Each field gets its own provider instance (own session token).
            const provider = this.mode === 'google'
                ? new GooglePlacesProvider()
                : this.mode === 'proxy' ? new ServerProxyProvider(root.dataset.endpoint) : null;
            return new PlaceAutocomplete(root, provider, () => this.fail());
        });

        scope.querySelector('[data-places-retry]')?.addEventListener('click', () => this.retry());
        window.addEventListener('places:auth-failure', () => this.fail());

        // Lets the booking form check that every location came from Google.
        scope.placeSearch = this;
        this.start();
    }

    async start() {
        if (!['google', 'proxy'].includes(this.mode)) {
            this.fail();
            return;
        }

        this.fields.forEach((field) => field.disable(MESSAGES.starting));
        this.emit();

        try {
            // Every field must be ready (each Google provider loads the same library).
            await Promise.all(this.fields.map((field) => field.provider.ready()));
        } catch {
            this.fail();
            return;
        }

        if (this.state === 'unavailable' || window.__h2PlacesAuthFailed) {
            this.fail();
            return;
        }

        this.state = 'ready';
        this.fields.forEach((field) => field.enable());
        this.toggleNotice(false);
        this.emit();
    }

    fail() {
        if (this.state === 'unavailable') {
            return;
        }
        this.state = 'unavailable';
        this.fields.forEach((field) => field.disable(UNAVAILABLE_MESSAGE));
        this.toggleNotice(true);
        this.emit();
    }

    /**
     * Try again by reloading the page (a rejected Google key or script cannot
     * be re-initialised in place). Everything already entered is restored.
     */
    retry() {
        const form = this.scope.closest('form') ?? this.scope.querySelector('form');
        if (form) {
            saveDraft(form);
        }
        window.location.reload();
    }

    /** Fields with typed text that was not chosen from the suggestions. */
    incompleteFields() {
        return this.fields.filter((field) => !field.input.closest('[hidden]') && !field.isComplete());
    }

    toggleNotice(show) {
        if (this.notice) {
            this.notice.hidden = !show;
        }
    }

    emit() {
        this.scope.dispatchEvent(new CustomEvent('places:state', { bubbles: true, detail: { state: this.state } }));
    }
}

export function initPlaceAutocomplete() {
    // Google calls this when the browser key is invalid or not authorised for this site.
    window.addEventListener('places:auth-failure', () => {
        window.__h2PlacesAuthFailed = true;
    });

    const scopes = new Map();
    document.querySelectorAll('[data-place-field]').forEach((root) => {
        const scope = root.closest('form') ?? document.body;
        scopes.set(scope, [...(scopes.get(scope) ?? []), root]);
    });

    scopes.forEach((roots, scope) => new LocationSearch(scope, roots));
}
