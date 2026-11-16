{{--
    Compact homepage booking form (three trip tabs, short fields). It posts the
    same fields to the same route as the full /booking form (components/booking/
    form.blade.php) and uses the same behaviour in resources/js/booking.js
    (data-* hooks), so validation, location search, vehicle availability,
    rental packages and the live estimate work identically.

    Trip types: the four booking_type radios are kept. "Outstation" selects
    One way; a One way / Round trip switch appears while Outstation is active.
--}}
@props(['booking', 'distanceEnabled' => false])

@php
    use App\Enums\BookingType;

    $defaults = $booking['defaults'];
    $value = fn (string $key, $fallback = null) => old($key, $defaults[$key] ?? $fallback);
    $currentType = BookingType::tryFrom((string) $value('booking_type')) ?? BookingType::Local;
    $currentVehicle = (int) $value('vehicle_id');
    $selectedSights = (array) $value('sightseeing', []);
    $mode = $booking['places']['mode'];
    $p = 'home';

    $packagesByVehicle = $booking['vehicles']->mapWithKeys(fn ($vehicle) => [
        $vehicle->id => $vehicle->fareFor(BookingType::Rental)?->packages->where('is_active', true) ?? collect(),
    ]);
    $anyPackages = $packagesByVehicle->contains(fn ($packages) => $packages->isNotEmpty());
    $dropLabels = ['local' => 'Drop Location', 'rental' => 'Drop Location (optional)', 'outstation_one_way' => 'Destination', 'outstation_round_trip' => 'Destination'];
    $submitLabels = ['local' => 'Book Local Taxi', 'rental' => 'Book Rental', 'outstation_one_way' => 'Book Outstation Taxi', 'outstation_round_trip' => 'Book Outstation Taxi'];
    $outstation = $currentType->isOutstation();
@endphp

@if ($booking['places']['browserKey'])
    <x-booking.maps-loader :api-key="$booking['places']['browserKey']" />
@endif

<form method="POST" action="{{ route('booking.store') }}" novalidate
    {{ $attributes->merge(['class' => 'booking-form booking-compact']) }}
    data-booking-form data-variant="home"
    data-estimate-url="{{ route('booking.estimate') }}"
    data-vehicle-options='@json($booking['vehicleOptions'])'
    data-drop-labels='@json($dropLabels)'>
    @csrf
    <input type="hidden" name="form_token" value="{{ $booking['formToken'] }}">

    @if ($booking['unavailableVehicle'])
        <p class="mb-3 flex gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-900 ring-1 ring-amber-200" role="status">
            <x-ui.icon name="info" class="size-4 shrink-0" /> The vehicle you selected is not available at the moment. Please choose another vehicle.
        </p>
    @endif

    {{-- Trip type: Local / Rental / Outstation tabs over the four booking_type radios --}}
    <fieldset id="{{ $p }}-booking_type">
        <legend class="sr-only">Trip type</legend>
        <div class="trip-tabs">
            <label class="trip-tab">
                <input type="radio" name="booking_type" value="local" class="sr-only" data-type-input data-label="Local" aria-label="Local" @checked($currentType === BookingType::Local) required>
                Local
            </label>
            <label class="trip-tab">
                <input type="radio" name="booking_type" value="rental" class="sr-only" data-type-input data-label="Rental" aria-label="Rental" @checked($currentType === BookingType::Rental)>
                Rental
            </label>
            <label class="trip-tab trip-tab-outstation">
                <input type="radio" id="{{ $p }}-type-oneway" name="booking_type" value="outstation_one_way" class="sr-only" data-type-input data-label="Outstation One Way" aria-label="Outstation one way" @checked($currentType === BookingType::OutstationOneWay)>
                Outstation
            </label>
        </div>
        {{-- One way / Round trip, shown while an Outstation type is selected (CSS :has). --}}
        <div class="trip-subtabs" role="group" aria-label="Outstation trip">
            <label for="{{ $p }}-type-oneway" class="trip-subtab trip-subtab-oneway" aria-hidden="true">One way</label>
            <label class="trip-subtab trip-subtab-round">
                <input type="radio" name="booking_type" value="outstation_round_trip" class="sr-only" data-type-input data-label="Outstation Round Trip" aria-label="Outstation round trip" @checked($currentType === BookingType::OutstationRoundTrip)>
                Round trip
            </label>
        </div>
        <x-form.field-error name="booking_type" />
    </fieldset>

    {{-- Shown when Google location search cannot be used; place-autocomplete.js toggles it. --}}
    <div data-places-notice role="alert" @if ($mode !== 'unavailable') hidden @endif
        class="mt-3 flex flex-col gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-900 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between">
        <p class="flex gap-2"><x-ui.icon name="alert" class="size-4 shrink-0" /> <span>Google location search is unavailable. Please try again later. Your other details are kept.</span></p>
        <button type="button" class="btn btn-outline min-h-8 shrink-0 bg-white px-3 py-1 text-xs" data-places-retry>
            <x-ui.icon name="repeat" class="size-3.5" /> Try again
        </button>
    </div>
    <noscript>
        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-900 ring-1 ring-amber-200">Location search needs JavaScript. Please enable it, or call us to book.</p>
    </noscript>

    {{-- Journey: pickup / drop --}}
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <x-form.location-field name="pickup" label="Pickup Location" :id-prefix="$p" :value="$value('pickup')" :place-id="$value('pickup_place_id')"
            placeholder="Hotel, street or station" required :mode="$mode" marker="bg-brand-green-500" class="min-w-0" />
        <div data-dropoff-wrapper class="min-w-0">
            <x-form.location-field name="dropoff" :label="$dropLabels[$currentType->value]" :id-prefix="$p" :value="$value('dropoff')" :place-id="$value('dropoff_place_id')"
                :placeholder="$outstation ? 'e.g. Chennai Central or Mahabalipuram' : 'e.g. Auroville Visitors Centre'"
                :required="$currentType !== BookingType::Rental" :mode="$mode" marker="bg-brand-blue-600" />
        </div>
    </div>

    {{-- Date / Time / Vehicle Type --}}
    <div class="mt-4 grid grid-cols-1 gap-4 min-[360px]:grid-cols-2 sm:grid-cols-3">
        <div class="min-w-0">
            <label for="{{ $p }}-travel_date" class="form-label"><span data-date-label data-text-default="Date" data-text-outstation="Departure Date">{{ $outstation ? 'Departure Date' : 'Date' }}</span> <span class="text-red-600" aria-hidden="true">*</span></label>
            <input type="date" id="{{ $p }}-travel_date" name="travel_date" value="{{ $value('travel_date') }}" min="{{ $booking['minDate'] }}" max="{{ $booking['maxDate'] }}" required class="form-control" data-travel-date @error('travel_date') aria-invalid="true" @enderror>
            <x-form.field-error name="travel_date" :id="$p.'-travel_date'" />
        </div>
        <div class="min-w-0">
            <label for="{{ $p }}-pickup_time" class="form-label">Time <span class="text-red-600" aria-hidden="true">*</span></label>
            <input type="time" id="{{ $p }}-pickup_time" name="pickup_time" value="{{ $value('pickup_time') }}" step="300" required class="form-control" @error('pickup_time') aria-invalid="true" @enderror>
            <x-form.field-error name="pickup_time" :id="$p.'-pickup_time'" />
        </div>
        <div class="min-w-0 min-[360px]:col-span-2 sm:col-span-1" id="{{ $p }}-vehicle_id">
            <label for="{{ $p }}-vehicle" class="form-label">Vehicle Type <span class="text-red-600" aria-hidden="true">*</span></label>
            {{--
                Only vehicles with a bookable fare for the selected trip type are listed; booking.js
                re-filters on type change. With none, the select is disabled and says so.
            --}}
            @php($noneForType = collect($booking['vehicleOptions'])->every(fn ($types) => ! ($types[$currentType->value]['available'] ?? false)))
            <select id="{{ $p }}-vehicle" name="vehicle_id" required class="form-control disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted" data-vehicle-select
                aria-describedby="{{ $p }}-vehicle-empty" @disabled($noneForType) @error('vehicle_id') aria-invalid="true" @enderror>
                <option value="" data-vehicle-placeholder data-text-choose="Choose a vehicle" data-text-none="No vehicles available for">{{ $noneForType ? 'No vehicles available for '.$currentType->label() : 'Choose a vehicle' }}</option>
                @foreach ($booking['vehicles'] as $vehicle)
                    @php($option = $booking['vehicleOptions'][$vehicle->id][$currentType->value] ?? ['available' => false])
                    <option value="{{ $vehicle->id }}" data-vehicle-option data-vehicle-id="{{ $vehicle->id }}" data-name="{{ $vehicle->name }}"
                        @selected($option['available'] && $currentVehicle === $vehicle->id) @disabled(! $option['available']) @if (! $option['available']) hidden @endif>{{ $vehicle->name }}{{ $option['available'] && ($option['rate'] ?? '') ? ' — '.$option['rate'] : '' }}</option>
                @endforeach
            </select>
            <x-form.field-error name="vehicle_id" />
        </div>
    </div>
    <p id="{{ $p }}-vehicle-empty" class="mt-3 flex gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-sm text-amber-900 ring-1 ring-amber-200" data-vehicle-empty role="status" @unless ($noneForType) hidden @endunless>
        <x-ui.icon name="info" class="mt-0.5 size-4 shrink-0" />
        <span>
            <strong>No vehicles are available for <span data-vehicle-empty-type>{{ $currentType->label() }}</span> online booking right now.</strong>
            Please choose another trip type, or <a href="{{ route('contact') }}" class="font-semibold underline">contact us</a> to book.
        </span>
    </p>
    <p class="form-help" data-vehicle-message aria-live="polite"></p>

    {{-- Outstation round trip: return date and time --}}
    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3" data-type-section="outstation_round_trip" @if ($currentType !== BookingType::OutstationRoundTrip) hidden @endif>
        <div class="min-w-0">
            <label for="{{ $p }}-return_date" class="form-label">Return Date <span class="text-red-600" aria-hidden="true">*</span></label>
            <input type="date" id="{{ $p }}-return_date" name="return_date" value="{{ $value('return_date') }}" min="{{ $booking['minDate'] }}" max="{{ $booking['maxDate'] }}" class="form-control" data-return-date @error('return_date') aria-invalid="true" @enderror>
            <x-form.field-error name="return_date" :id="$p.'-return_date'" />
        </div>
        <div class="min-w-0">
            <label for="{{ $p }}-return_time" class="form-label">Return Time <span class="font-normal text-muted">(optional)</span></label>
            <input type="time" id="{{ $p }}-return_time" name="return_time" value="{{ $value('return_time') }}" step="300" class="form-control" @error('return_time') aria-invalid="true" @enderror>
            <x-form.field-error name="return_time" :id="$p.'-return_time'" />
        </div>
    </div>

    {{-- Rental: one of the chosen vehicle's packages, plus optional places to visit --}}
    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2" data-type-section="rental" @if ($currentType !== BookingType::Rental) hidden @endif>
        <div data-package-wrapper class="min-w-0">
            <label for="{{ $p }}-rental_package_id" class="form-label">Rental Package <span class="text-red-600" aria-hidden="true">*</span></label>
            <select id="{{ $p }}-rental_package_id" name="rental_package_id" class="form-control" data-package-select @error('rental_package_id') aria-invalid="true" @enderror>
                <option value="">{{ $anyPackages ? 'Choose a package' : 'No rental packages available' }}</option>
                @foreach ($booking['vehicles'] as $vehicle)
                    @if ($packagesByVehicle[$vehicle->id]->isNotEmpty())
                        <optgroup label="{{ $vehicle->name }}" data-vehicle="{{ $vehicle->id }}">
                            @foreach ($packagesByVehicle[$vehicle->id] as $package)
                                <option value="{{ $package->id }}" @selected((int) $value('rental_package_id') === $package->id)>{{ $package->summary() }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
            <p class="form-help">Packages shown are for the selected vehicle.</p>
            <x-form.field-error name="rental_package_id" :id="$p.'-rental_package_id'" />
        </div>
        <details class="rounded-lg border border-line bg-surface px-3 py-2" @if ($selectedSights) open @endif>
            <summary class="cursor-pointer text-xs font-semibold text-brand-blue-900">Add places you would like to visit (optional)</summary>
            <div class="mt-2 grid gap-1.5 sm:grid-cols-2" id="{{ $p }}-sightseeing">
                @foreach ($booking['destinations'] as $destination)
                    <label class="flex min-h-9 min-w-0 cursor-pointer items-center gap-2 rounded-lg border border-line bg-white px-2.5 py-1.5 text-xs font-medium has-checked:border-brand-green-600 has-checked:bg-brand-green-50">
                        <input type="checkbox" name="sightseeing[]" value="{{ $destination['slug'] }}" class="size-4 shrink-0 accent-brand-green-600" @checked(in_array($destination['slug'], $selectedSights, true))>
                        <span class="min-w-0">{{ $destination['name'] }}</span>
                    </label>
                @endforeach
            </div>
        </details>
    </div>

    {{-- Live estimate (compact) --}}
    {{-- data-estimate-theme="light": booking.js renders the estimate in dark text for this light box. --}}
    <div class="mt-4 w-full rounded-xl border border-[#d9e2f0] bg-[#eef4ff] p-3.5 text-[#12336b] sm:p-4" data-estimate data-estimate-theme="light" aria-live="polite">
        <div class="flex items-center justify-between gap-2">
            <p class="flex items-center gap-2 text-sm font-bold text-[#12336b]"><x-ui.icon name="receipt" class="size-5 shrink-0 text-[#16a765]" /> Fare Estimate</p>
            <span class="hidden size-4 animate-spin rounded-full border-2 border-[#d9e2f0] border-t-[#12336b]" data-estimate-spinner aria-hidden="true"></span>
        </div>
        <div class="mt-1.5 text-base leading-normal text-[#12336b]" data-estimate-body
            data-default="{{ $distanceEnabled ? 'Choose your trip and vehicle to see an estimate.' : 'The fare depends on your route and is confirmed by our team when you send the request.' }}">
            {{ $distanceEnabled ? 'Choose your trip and vehicle to see an estimate.' : 'The fare depends on your route and is confirmed by our team when you send the request.' }}
        </div>
    </div>

    {{-- Your details --}}
    <div class="mt-6 border-t border-[#d9e2f0] pt-5">
        <h3 class="text-sm font-bold text-brand-blue-950">Your Details</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div class="min-w-0">
                <label for="{{ $p }}-name" class="form-label">Name <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="text" id="{{ $p }}-name" name="name" value="{{ $value('name') }}" required maxlength="80" autocomplete="name" class="form-control" @error('name') aria-invalid="true" @enderror>
                <x-form.field-error name="name" :id="$p.'-name'" />
            </div>
            <div class="min-w-0">
                <label for="{{ $p }}-phone" class="form-label">Phone <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="tel" id="{{ $p }}-phone" name="phone" value="{{ $value('phone') }}" required minlength="10" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" autocomplete="tel-national" placeholder="9876543210"
                    data-phone-input aria-describedby="{{ $p }}-phone-help @error('phone') {{ $p }}-phone-error @enderror" title="Enter a 10-digit mobile number" class="form-control" @error('phone') aria-invalid="true" @enderror>
                <p id="{{ $p }}-phone-help" class="form-help">10 digits, without +91 or 0.</p>
                <x-form.field-error name="phone" :id="$p.'-phone'" />
            </div>
            <div class="min-w-0">
                <label for="{{ $p }}-passengers" class="form-label">Passengers <span class="text-red-600" aria-hidden="true">*</span></label>
                <select id="{{ $p }}-passengers" name="passengers" required class="form-control" @error('passengers') aria-invalid="true" @enderror>
                    @foreach (range(1, config('site.booking.max_passengers')) as $count)
                        <option value="{{ $count }}" @selected((int) $value('passengers', 1) === $count)>{{ $count }}</option>
                    @endforeach
                </select>
                <x-form.field-error name="passengers" :id="$p.'-passengers'" />
            </div>
        </div>
        <div class="mt-3">
            <label for="{{ $p }}-notes" class="form-label">Additional Requests <span class="font-normal text-muted">(optional)</span></label>
            <textarea id="{{ $p }}-notes" name="notes" rows="2" maxlength="500" placeholder="Luggage, stops, flight or train number…" class="form-control" @error('notes') aria-invalid="true" @enderror>{{ $value('notes') }}</textarea>
            <x-form.field-error name="notes" :id="$p.'-notes'" />
        </div>
    </div>

    <div class="mt-5">
        <button type="submit" class="btn btn-primary w-full sm:w-auto" data-type-labels='@json($submitLabels)' data-submit disabled>
            <span data-submit-label>{{ $submitLabels[$currentType->value] }}</span> <x-ui.icon name="arrow-right" class="size-4" />
        </button>
        <p class="mt-2 text-[11px] leading-snug text-muted">
            Next you review your request{{ $booking['whatsappConfigured'] ? ' and send it on WhatsApp' : '' }}. H2 CABS confirms availability and the final fare with you.
        </p>
    </div>

    @if ($mode === 'unavailable' && app()->isLocal())
        <p class="mt-3 rounded-lg bg-surface p-2 font-mono text-[11px] text-muted">
            Developer note: Google location search is not configured, so bookings cannot be submitted. Set GOOGLE_MAPS_BROWSER_KEY or GOOGLE_PLACES_API_KEY in .env.
        </p>
    @endif
</form>
