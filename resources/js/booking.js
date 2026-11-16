/**
 * Booking form behaviour for <x-booking.form> (homepage widget and /booking
 * page). Progressive enhancement only — the server validates everything and
 * calculates every fare (/booking/estimate). Vehicle availability, rates and
 * rental packages come from the server (data-vehicle-options); nothing about
 * pricing is hardcoded here.
 */

const ESTIMATE_DEBOUNCE_MS = 400;

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) {
        node.className = className;
    }
    if (text !== undefined) {
        node.textContent = text;
    }
    return node;
}

function initBookingForm(form) {
    const options = JSON.parse(form.dataset.vehicleOptions || '{}');
    const dropLabels = JSON.parse(form.dataset.dropLabels || '{}');
    const sections = form.querySelectorAll('[data-type-section]');
    const vehicleOptions = form.querySelectorAll('[data-vehicle-option]');
    const vehicleMessage = form.querySelector('[data-vehicle-message]');
    const vehicleEmpty = form.querySelector('[data-vehicle-empty]');
    const dropoffInput = form.querySelector('[name="dropoff"]');
    const dropoffWrapper = form.querySelector('[data-dropoff-wrapper]');
    const dateLabel = form.querySelector('[data-date-label]');
    const packageSelect = form.querySelector('[data-package-select]');
    const estimateBody = form.querySelector('[data-estimate-body]');
    const estimateSpinner = form.querySelector('[data-estimate-spinner]');
    const estimateDefault = estimateBody?.dataset.default ?? '';

    // The compact homepage form uses a vehicle <select> (its <option>s carry
    // data-vehicle-option); the /booking page uses radio cards.
    const vehicleSelect = form.querySelector('[data-vehicle-select]');
    const submitLabels = JSON.parse(form.querySelector('[data-submit]')?.dataset.typeLabels || 'null');

    const typeInput = () => form.querySelector('[data-type-input]:checked');
    const currentType = () => typeInput()?.value;
    const currentVehicle = () => (vehicleSelect
        ? (vehicleSelect.value ? vehicleSelect.selectedOptions[0] : null)
        : form.querySelector('[data-vehicle-input]:checked'));
    const optionFor = (vehicleId, type = currentType()) => options[vehicleId]?.[type] ?? { available: false };

    const setFieldsEnabled = (container, enabled) => {
        container.querySelectorAll('input, select, textarea').forEach((field) => {
            field.disabled = !enabled;
        });
    };

    // --- Booking type -------------------------------------------------------

    const applyType = () => {
        const type = currentType();
        const outstation = type === 'outstation_one_way' || type === 'outstation_round_trip';

        sections.forEach((section) => {
            const active = section.dataset.typeSection === type;
            section.hidden = !active;
            setFieldsEnabled(section, active);
        });

        // Rentals may end where they started; every other type needs a drop.
        const needsDrop = type !== 'rental';
        dropoffInput.required = needsDrop;
        dropoffInput.setAttribute('aria-required', String(needsDrop));
        dropoffWrapper.querySelector('[data-label-text]').textContent = dropLabels[type] ?? 'Destination';
        dropoffWrapper.querySelector('[data-required-mark]').hidden = !needsDrop;
        dropoffInput.placeholder = outstation ? 'e.g. Chennai Central or Mahabalipuram' : 'e.g. Auroville Visitors Centre';
        if (dateLabel) {
            // A form may supply its own wording (the compact form says "Date").
            dateLabel.textContent = outstation
                ? (dateLabel.dataset.textOutstation ?? 'Departure date')
                : (dateLabel.dataset.textDefault ?? 'Pickup date');
        }
        form.querySelector('[data-return-date]').required = type === 'outstation_round_trip';

        // Compact form: the button says what is being booked ("Book Local Taxi").
        const submitLabel = form.querySelector('[data-submit-label]');
        if (submitLabels?.[type] && submitLabel && form.dataset.submitting !== 'true') {
            submitLabel.textContent = submitLabels[type];
        }

        applyVehicles();
    };

    // --- Vehicles -------------------------------------------------------------

    // Only vehicles with an approved fare for the selected type are shown.
    const applyVehicles = () => {
        const type = currentType();
        const typeLabel = typeInput()?.dataset.label ?? '';
        let deselected = null;

        if (vehicleSelect) {
            vehicleOptions.forEach((option) => {
                const data = optionFor(option.dataset.vehicleId, type);

                option.disabled = !data.available;
                option.hidden = !data.available;
                option.textContent = data.available && data.rate ? `${option.dataset.name} — ${data.rate}` : option.dataset.name;

                if (!data.available && option.selected) {
                    vehicleSelect.value = '';
                    deselected = option.dataset.name;
                }
            });

            if (!currentVehicle()) {
                const first = [...vehicleOptions].find((option) => !option.disabled);
                if (first) {
                    vehicleSelect.value = first.value;
                }
            }
        } else {
            vehicleOptions.forEach((option) => {
                const input = option.querySelector('[data-vehicle-input]');
                const data = optionFor(option.dataset.vehicleId, type);

                input.disabled = !data.available;
                option.hidden = !data.available;
                option.querySelector('[data-vehicle-rate]').textContent = data.available ? (data.rate ?? '') : '';

                if (!data.available && input.checked) {
                    input.checked = false;
                    deselected = input.dataset.name;
                }
            });

            if (!currentVehicle()) {
                const first = [...vehicleOptions].map((o) => o.querySelector('[data-vehicle-input]')).find((input) => !input.disabled);
                if (first) {
                    first.checked = true;
                }
            }
        }

        const available = [...vehicleOptions].some((o) => (vehicleSelect ? !o.disabled : !o.querySelector('[data-vehicle-input]').disabled));

        // Compact form: with no bookable vehicle for this trip type, say so in
        // the (disabled) select rather than leaving an empty "Choose a vehicle".
        if (vehicleSelect) {
            const placeholder = vehicleSelect.querySelector('[data-vehicle-placeholder]');
            if (placeholder) {
                placeholder.textContent = available
                    ? placeholder.dataset.textChoose
                    : `${placeholder.dataset.textNone} ${typeLabel}`;
            }
            vehicleSelect.disabled = !available;
        }
        if (vehicleEmpty) {
            vehicleEmpty.hidden = available;
            vehicleEmpty.querySelector('[data-vehicle-empty-type]').textContent = typeLabel;
        }
        if (vehicleMessage) {
            vehicleMessage.textContent = available && deselected
                ? `${deselected} is not offered for ${typeLabel}, so we selected ${currentVehicle()?.dataset.name}.`
                : '';
        }

        applyRental();
        requestEstimate();
    };

    // --- Rental: the chosen vehicle's packages -------------------------------------

    const applyRental = () => {
        if (currentType() !== 'rental' || !packageSelect) {
            return;
        }

        const vehicleId = currentVehicle()?.value;
        packageSelect.querySelectorAll('optgroup').forEach((group) => {
            const mine = group.dataset.vehicle === vehicleId;
            group.hidden = !mine;
            group.disabled = !mine;
        });
        if (packageSelect.selectedOptions[0]?.parentElement?.disabled) {
            packageSelect.value = '';
        }
        // A single package is chosen for the customer.
        const packages = optionFor(vehicleId, 'rental').packages ?? [];
        if (!packageSelect.value && packages.length === 1) {
            packageSelect.value = String(packages[0].id);
        }
    };

    // --- Live estimate (server-calculated) -----------------------------------

    let timer = null;
    let controller = null;

    const requestEstimate = () => {
        clearTimeout(timer);
        // Never leave a quote for a previous trip type or vehicle on screen.
        controller?.abort();
        if (estimateBody) {
            estimateBody.replaceChildren(el('p', tone.meta, 'Updating estimate…'));
            estimateSpinner?.classList.remove('hidden');
        }
        timer = setTimeout(fetchEstimate, ESTIMATE_DEBOUNCE_MS);
    };

    const fetchEstimate = async () => {
        if (!estimateBody) {
            return;
        }
        if (!currentVehicle()) {
            estimateSpinner?.classList.add('hidden');
            renderMessage('Choose a vehicle to see an estimate.');
            return;
        }

        const data = new FormData(form);
        const payload = {};
        ['booking_type', 'vehicle_id', 'rental_package_id', 'rental_duration', 'pickup', 'pickup_place_id', 'dropoff', 'dropoff_place_id', 'travel_date', 'return_date']
            .forEach((key) => {
                const value = data.get(key);
                if (value) {
                    payload[key] = value;
                }
            });

        controller?.abort();
        controller = new AbortController();
        estimateSpinner?.classList.remove('hidden');

        try {
            const response = await fetch(form.dataset.estimateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': data.get('_token') },
                body: JSON.stringify(payload),
                signal: controller.signal,
            });
            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                renderMessage(response.status === 429
                    ? 'Too many updates — the estimate will be included in your request summary.'
                    : (body.message ?? estimateDefault));
                return;
            }

            renderEstimate(body);
        } catch (error) {
            if (error.name !== 'AbortError') {
                renderMessage('The estimate could not be loaded. You can still send your request.');
            }
        } finally {
            estimateSpinner?.classList.add('hidden');
        }
    };

    // The /booking page shows the estimate in a dark box (white text); the
    // compact homepage form uses a light box (data-estimate-theme="light").
    const lightEstimate = form.querySelector('[data-estimate]')?.dataset.estimateTheme === 'light';
    const tone = lightEstimate
        ? {
            message: 'text-base leading-normal text-[#12336b]',
            eyebrow: 'text-xs font-semibold tracking-wide text-[#475569] uppercase',
            total: 'font-display text-3xl font-bold text-[#12336b]',
            meta: 'text-sm leading-normal text-[#475569]',
            lines: 'mt-3 space-y-1 border-t border-[#d9e2f0] pt-3 text-sm leading-normal text-[#12336b]',
            lineLabel: 'text-[#475569]',
            headline: 'text-base font-semibold leading-normal text-[#12336b]',
            notes: 'mt-2 list-disc space-y-0.5 pl-5 text-[13px] leading-normal text-[#475569]',
        }
        : {
            message: '',
            eyebrow: 'text-xs font-semibold tracking-wide text-brand-green-200 uppercase',
            total: 'font-display text-3xl font-bold text-white',
            meta: 'text-white/75',
            lines: 'mt-3 space-y-1 border-t border-white/15 pt-3',
            lineLabel: 'text-white/70',
            headline: 'text-lg font-bold text-white',
            notes: 'mt-3 list-disc space-y-0.5 pl-5 text-xs text-white/65',
        };

    const renderMessage = (text) => estimateBody.replaceChildren(el('p', tone.message, text));

    const renderEstimate = (quote) => {
        const estimate = quote.estimate;

        if (!estimate) {
            renderMessage(estimateDefault);
            return;
        }

        const nodes = [];

        if (estimate.status === 'estimated' && estimate.total) {
            nodes.push(el('p', tone.eyebrow, 'Estimated fare — not a confirmed quote'));
            nodes.push(el('p', tone.total, `≈ ${estimate.total}`));
            if (quote.distance) {
                nodes.push(el('p', tone.meta, quote.distance));
            }
            const list = el('ul', tone.lines);
            estimate.lines.forEach((line) => {
                const item = el('li', 'flex justify-between gap-3');
                item.append(el('span', tone.lineLabel, line.label), el('span', 'font-semibold', line.amount));
                list.append(item);
            });
            nodes.push(list);
        } else {
            // No amount is ever shown unless the server says the rate is approved
            // and the trip could be priced.
            const needsRoute = estimate.reason === 'no_distance'
                && (!form.querySelector('[name="pickup_place_id"]')?.value || !form.querySelector('[name="dropoff_place_id"]')?.value);
            nodes.push(el('p', tone.headline, needsRoute
                ? 'Choose your pickup and drop locations to see an estimate.'
                : estimate.headline));
            if (quote.distance) {
                nodes.push(el('p', tone.meta, quote.distance));
            }
        }

        const facts = [...(estimate.details ?? []), ...(estimate.notes ?? [])];
        if (facts.length) {
            const notes = el('ul', tone.notes);
            facts.forEach((note) => notes.append(el('li', '', note)));
            nodes.push(notes);
        }

        estimateBody.replaceChildren(...nodes);
    };

    // --- Live trip summary (booking page aside) --------------------------------

    const summary = document.querySelector(`[data-trip-summary="${form.id}"]`);
    const updateSummary = () => {
        if (!summary) {
            return;
        }
        const values = {
            type: typeInput()?.dataset.label,
            vehicle: currentVehicle()?.dataset.name,
            pickup: form.querySelector('[name="pickup"]').value.trim(),
            dropoff: dropoffInput.value.trim(),
            date: [form.querySelector('[name="travel_date"]').value, form.querySelector('[name="pickup_time"]').value].filter(Boolean).join(' · '),
        };
        Object.entries(values).forEach(([key, value]) => {
            const target = summary.querySelector(`[data-summary="${key}"]`);
            if (target) {
                target.textContent = value || '—';
            }
        });
    };

    // --- Wiring ------------------------------------------------------------------

    form.querySelectorAll('[data-type-input]').forEach((input) => input.addEventListener('change', applyType));
    form.querySelectorAll('[data-vehicle-input], [data-vehicle-select]').forEach((input) => input.addEventListener('change', () => {
        applyRental();
        requestEstimate();
    }));
    form.querySelectorAll('[name="rental_package_id"], [name="rental_duration"], [data-travel-date], [data-return-date], [name="pickup"], [name="dropoff"]')
        .forEach((input) => input.addEventListener('change', requestEstimate));
    form.addEventListener('place:selected', requestEstimate);
    form.addEventListener('input', updateSummary);
    form.addEventListener('change', updateSummary);

    // Vehicle chosen from a carousel card elsewhere on the page.
    form.addEventListener('booking:select-vehicle', (event) => {
        const input = vehicleSelect
            ? vehicleSelect.querySelector(`[data-vehicle-option][value="${event.detail.id}"]`)
            : form.querySelector(`[data-vehicle-input][value="${event.detail.id}"]`);
        if (!input) {
            return;
        }
        if (input.disabled) {
            // Switch to the first booking type that offers this vehicle.
            const type = Object.entries(options[event.detail.id] ?? {}).find(([, data]) => data.available)?.[0];
            const typeRadio = type && form.querySelector(`[data-type-input][value="${type}"]`);
            if (typeRadio) {
                typeRadio.checked = true;
                applyType();
            }
        }
        if (!input.disabled && vehicleSelect) {
            vehicleSelect.value = input.value;
            vehicleSelect.dispatchEvent(new Event('change', { bubbles: true }));
            vehicleSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            vehicleSelect.focus({ preventScroll: true });
        } else if (!input.disabled) {
            input.checked = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.closest('[data-vehicle-option]').scrollIntoView({ behavior: 'smooth', block: 'center' });
            input.focus({ preventScroll: true });
        }
    });

    // A return can't be before the outward journey.
    const travelDate = form.querySelector('[data-travel-date]');
    const returnDate = form.querySelector('[data-return-date]');
    travelDate.addEventListener('change', () => {
        if (travelDate.value) {
            returnDate.min = travelDate.value;
        }
    });

    // Mobile number: digits only, at most 10 (pasted "+91 98765 43210" keeps
    // nothing but digits; the server validates exactly 10).
    const phone = form.querySelector('[data-phone-input]');
    if (phone) {
        // The script enforces the 10-digit limit itself, so a pasted number
        // with spaces is not cut off by the browser's maxlength first.
        phone.removeAttribute('maxlength');
        const describe = () => phone.setCustomValidity(
            phone.value === '' || /^\d{10}$/.test(phone.value) ? '' : 'Please enter a 10-digit mobile number, using digits only.',
        );
        phone.addEventListener('input', () => {
            const digits = phone.value.replace(/\D/g, '');
            // A pasted number with a +91 or 0 prefix keeps its last 10 digits.
            const cleaned = digits.length > 10 && /^(91|0)/.test(digits) ? digits.slice(-10) : digits.slice(0, 10);
            if (cleaned !== phone.value) {
                phone.value = cleaned;
            }
            describe();
        });
        describe();
    }

    // Submission needs Google location search: it is blocked while search is
    // loading or unavailable (place-autocomplete.js reports "places:state").
    const button = form.querySelector('[data-submit]');
    const label = form.querySelector('[data-submit-label]');
    const idleLabel = label.textContent;
    let placesReady = form.placeSearch?.state === 'ready';

    const syncButton = () => {
        button.disabled = !placesReady || form.dataset.submitting === 'true';
    };

    form.addEventListener('places:state', (event) => {
        placesReady = event.detail.state === 'ready';
        syncButton();
    });

    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true' || !placesReady) {
            event.preventDefault();
            return;
        }

        // Every location must be chosen from the Google suggestions.
        const incomplete = form.placeSearch?.incompleteFields() ?? [];
        if (incomplete.length) {
            event.preventDefault();
            incomplete.forEach((field) => field.flagIncomplete());
            incomplete[0].input.focus();
            return;
        }

        // Guard against double submission.
        form.dataset.submitting = 'true';
        // Stop any pending estimate so it cannot race the submission.
        clearTimeout(timer);
        controller?.abort();
        button.setAttribute('aria-disabled', 'true');
        button.classList.add('opacity-70', 'pointer-events-none');
        label.textContent = 'Preparing your request…';
    });
    window.addEventListener('pageshow', () => {
        form.dataset.submitting = 'false';
        button.removeAttribute('aria-disabled');
        button.classList.remove('opacity-70', 'pointer-events-none');
        label.textContent = submitLabels?.[currentType()] ?? idleLabel;
        syncButton();
    });

    form.querySelector('[data-error-summary]')?.focus();
    applyType();
    updateSummary();
}

/** Horizontal vehicle carousel: buttons, keyboard arrows, swipe (native scroll). */
function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const prev = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');
        const step = () => (track?.querySelector('[data-carousel-item]')?.offsetWidth ?? 300) + 24;

        // With a single vehicle (or none) the arrows are not rendered.
        if (!track || !prev || !next) {
            return;
        }

        const update = () => {
            prev.disabled = track.scrollLeft <= 4;
            next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
        };

        prev.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
        next.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
        track.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                event.preventDefault();
                track.scrollBy({ left: event.key === 'ArrowRight' ? step() : -step(), behavior: 'smooth' });
            }
        });
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });

    // "Select this vehicle" buttons hand the choice to the booking form on the same page.
    document.querySelectorAll('[data-select-vehicle]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const form = document.querySelector('[data-booking-form]');
            if (!form) {
                return; // Fall back to the link (/booking?vehicle=…).
            }
            event.preventDefault();
            form.dispatchEvent(new CustomEvent('booking:select-vehicle', { detail: { id: link.dataset.selectVehicle } }));
        });
    });
}

function initCopyMessage() {
    const button = document.querySelector('[data-copy-message]');
    const text = document.querySelector('[data-message-text]');

    if (!button || !text || !navigator.clipboard) {
        return;
    }

    button.hidden = false;
    button.addEventListener('click', async () => {
        const label = button.querySelector('[data-copy-label]');
        try {
            await navigator.clipboard.writeText(text.textContent);
            label.textContent = 'Copied';
        } catch {
            label.textContent = 'Copy failed — select the text instead';
        }
    });
}

// Tell the server the customer opened WhatsApp (recorded as "opened", never as
// "sent" — only WhatsApp knows whether Send was pressed).
function initWhatsAppBeacon() {
    const link = document.querySelector('[data-whatsapp-beacon]');
    const token = link?.dataset.csrf;

    if (!link || !token || !navigator.sendBeacon) {
        return;
    }

    link.addEventListener('click', () => {
        const data = new FormData();
        data.append('_token', token);
        navigator.sendBeacon(link.dataset.whatsappBeacon, data);
    });
}

function initDestinationFilters() {
    const group = document.querySelector('[data-destination-filters]');

    if (!group) {
        return;
    }

    const cards = document.querySelectorAll('[data-category]');
    const count = document.querySelector('[data-destination-count]');
    group.hidden = false;

    group.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter]');

        if (!button) {
            return;
        }

        const filter = button.dataset.filter;
        group.querySelectorAll('[data-filter]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));

        let visible = 0;
        cards.forEach((card) => {
            const show = filter === 'all' || card.dataset.category === filter;
            card.hidden = !show;
            visible += show ? 1 : 0;
        });

        if (count) {
            count.textContent = `Showing ${visible} ${visible === 1 ? 'destination' : 'destinations'}.`;
        }
    });
}

export function initBooking() {
    document.querySelectorAll('[data-booking-form]').forEach((form, i) => {
        form.id ||= `booking-form-${i + 1}`;
        initBookingForm(form);
    });
    initCarousels();
    initCopyMessage();
    initWhatsAppBeacon();
    initDestinationFilters();
}
