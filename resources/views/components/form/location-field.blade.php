{{--
    Pickup / destination field. Locations must be chosen from Google
    suggestions; there is no manual-entry fallback.

    mode = "google":      Google Places in the browser (Maps JavaScript API)
           "proxy":       Google Places through the server (/places/search)
           "unavailable": no Google key configured — the field stays disabled

    The input is rendered DISABLED and resources/js/place-autocomplete.js
    enables it only once Google search is ready (ARIA 1.2 combobox).
    Choosing a suggestion stores the Google place ID in {name}_place_id,
    which the server requires.
--}}
@props(['name', 'label', 'value' => null, 'placeId' => null, 'placeholder' => '', 'required' => false, 'mode' => 'unavailable', 'icon' => 'pin', 'marker' => null, 'idPrefix' => 'field'])

@php
    $id = $idPrefix.'-'.$name;
    $search = in_array($mode, ['google', 'proxy'], true);
    $error = $errors->first($name) ?: $errors->first($name.'_place_id');
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} data-place-field data-place-mode="{{ $search ? $mode : 'unavailable' }}" @if ($mode === 'proxy') data-endpoint="{{ route('places.search') }}" @endif data-min-length="{{ config('site.places.min_query_length') }}">
    <label for="{{ $id }}" class="form-label">
        <span data-label-text>{{ $label }}</span> <span class="text-red-600" aria-hidden="true" data-required-mark @unless ($required) hidden @endunless>*</span>
    </label>

    <div class="relative">
        @if ($marker)
            <span aria-hidden="true" class="pointer-events-none absolute top-1/2 left-4 flex size-5 -translate-y-1/2 items-center justify-center rounded-full {{ $marker }} ring-4 ring-white"></span>
        @else
            <x-ui.icon :name="$icon" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-brand-blue-600" />
        @endif
        <input
            type="text"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            maxlength="200"
            autocomplete="off"
            disabled aria-disabled="true"
            @if ($required) required aria-required="true" @endif
            @if ($search) role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $id }}-listbox" @endif
            aria-describedby="{{ $id }}-help @if ($error) {{ $id }}-error @endif"
            @if ($error) aria-invalid="true" @endif
            class="form-control pr-11 pl-12 disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted"
            data-place-input
        >
        <input type="hidden" name="{{ $name }}_place_id" value="{{ $placeId }}" data-place-id>
        @if ($search)
            <span data-place-spinner hidden class="absolute top-1/2 right-4 size-5 -translate-y-1/2 animate-spin rounded-full border-2 border-brand-blue-200 border-t-brand-blue-600" aria-hidden="true"></span>
            <div data-place-popup hidden class="absolute inset-x-0 top-full z-30 mt-2 overflow-hidden rounded-2xl border border-line bg-white shadow-lift">
                <ul id="{{ $id }}-listbox" role="listbox" aria-label="{{ $label }} suggestions" class="max-h-72 overflow-y-auto p-1.5"></ul>
                {{-- Required attribution when showing Places results without a Google map. --}}
                <p class="border-t border-line px-3 py-1.5 text-right text-[11px] text-muted">powered by Google</p>
            </div>
        @endif
    </div>

    <p id="{{ $id }}-help" class="form-help" data-place-status aria-live="polite">
        {{ $search ? 'Loading Google location search…' : 'Google location search is unavailable. Please try again later.' }}
    </p>

    @if ($error)
        <p id="{{ $id }}-error" class="form-error"><x-ui.icon name="alert" class="mt-0.5 size-4" />{{ $error }}</p>
    @endif
</div>
