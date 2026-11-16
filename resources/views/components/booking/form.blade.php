{{--
    The one taxi booking form, shared by the homepage widget (variant="home")
    and the /booking page (variant="page") so both use the same booking types,
    fields, vehicles and validation. Data comes from App\Booking\BookingFormData.

    Behaviour (type switching, vehicle availability, rental packages, live
    estimate) is in resources/js/booking.js; every rule is enforced again on
    the server by StoreBookingRequest.
--}}
@props(['booking', 'variant' => 'page', 'distanceEnabled' => false])

@php
    use App\Enums\BookingType;

    $defaults = $booking['defaults'];
    $value = fn (string $key, $fallback = null) => old($key, $defaults[$key] ?? $fallback);
    $currentType = BookingType::tryFrom((string) $value('booking_type')) ?? BookingType::Local;
    $currentVehicle = (int) $value('vehicle_id');
    $selectedSights = (array) $value('sightseeing', []);
    $mode = $booking['places']['mode'];
    $p = $variant === 'home' ? 'home' : 'field';
    $isPage = $variant === 'page';

    $packagesByVehicle = $booking['vehicles']->mapWithKeys(fn ($vehicle) => [
        $vehicle->id => $vehicle->fareFor(BookingType::Rental)?->packages->where('is_active', true) ?? collect(),
    ]);
    $anyPackages = $packagesByVehicle->contains(fn ($packages) => $packages->isNotEmpty());

    // Selected trip card (same for every type): green border, light-blue background, green check.
    $tabSelected = 'has-checked:border-brand-green has-checked:bg-brand-blue-50 has-checked:shadow-lift [&:has(:checked)_.tab-icon]:bg-brand-green [&:has(:checked)_.tab-icon]:text-white';
    $dropLabels = ['local' => 'Drop location', 'rental' => 'Drop-off (optional)', 'outstation_one_way' => 'Destination city / address', 'outstation_round_trip' => 'Destination city / address'];
@endphp

@if ($booking['places']['browserKey'])
    <x-booking.maps-loader :api-key="$booking['places']['browserKey']" />
@endif

<form method="POST" action="{{ route('booking.store') }}" novalidate
    {{ $attributes->merge(['class' => 'booking-form']) }}
    data-booking-form data-variant="{{ $variant }}"
    data-estimate-url="{{ route('booking.estimate') }}"
    data-vehicle-options='@json($booking['vehicleOptions'])'
    data-drop-labels='@json($dropLabels)'>
    @csrf
    <input type="hidden" name="form_token" value="{{ $booking['formToken'] }}">

    @if ($isPage && $errors->any())
        <div class="mb-8 rounded-2xl border border-red-200 bg-red-50 p-5" role="alert" tabindex="-1" data-error-summary>
            <h2 class="flex items-center gap-2 font-bold text-red-800"><x-ui.icon name="alert" class="size-5" /> Please check the highlighted fields</h2>
            <ul class="mt-3 list-disc space-y-1 pl-6 text-sm text-red-800">
                @foreach ($errors->messages() as $field => $messages)
                    <li><a href="#field-{{ str_replace('_place_id', '', \Illuminate\Support\Str::before($field, '.')) }}" class="underline">{{ $messages[0] }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($booking['unavailableVehicle'])
        <p class="mb-6 flex gap-2 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200" role="status">
            <x-ui.icon name="info" class="size-5" /> The vehicle you selected is not available at the moment. Please choose another vehicle below.
        </p>
    @endif

    {{-- 1. Booking type --}}
    <fieldset>
        <legend class="booking-step"><span>1</span> What kind of trip?</legend>
        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4" id="{{ $p }}-booking_type">
            @foreach ($booking['bookingTypes'] as $type)
                <label class="group relative flex cursor-pointer flex-col gap-3 rounded-2xl border-2 border-line bg-white p-4 transition hover:border-brand-blue-200 has-focus-visible:ring-4 has-focus-visible:ring-brand-blue-100 {{ $tabSelected }}">
                    <input type="radio" name="booking_type" value="{{ $type->value }}" class="sr-only" data-type-input data-label="{{ $type->label() }}" @checked($currentType === $type) required>
                    <span class="tab-icon flex size-11 items-center justify-center rounded-xl bg-surface text-brand-blue-800 transition"><x-ui.icon :name="$type->icon()" /></span>
                    <span>
                        <span class="block font-bold leading-tight text-brand-blue-950">{{ $type->label() }}</span>
                        <span class="mt-0.5 block text-xs text-muted sm:text-sm">{{ $type->hint() }}</span>
                    </span>
                    <x-ui.icon name="check" class="absolute top-3 right-3 hidden size-5 text-brand-green-600 group-has-checked:block" />
                </label>
            @endforeach
        </div>
        <x-form.field-error name="booking_type" />
    </fieldset>

    {{-- 2. Route & time --}}
    <fieldset class="mt-8">
        <legend class="booking-step"><span>2</span> Where and when?</legend>

        {{-- Shown when Google location search cannot be used; place-autocomplete.js toggles it. --}}
        <div data-places-notice role="alert" @if ($mode !== 'unavailable') hidden @endif
            class="mt-4 flex flex-col gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex gap-2"><x-ui.icon name="alert" class="size-5 shrink-0" /> <span>Google location search is unavailable. Please try again later. Your other details are kept.</span></p>
            <button type="button" class="btn btn-outline shrink-0 bg-white py-2" data-places-retry>
                <x-ui.icon name="repeat" class="size-4" /> Try again
            </button>
        </div>
        <noscript>
            <p class="mt-4 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">Location search needs JavaScript. Please enable it, or call us to book.</p>
        </noscript>

        <div class="relative mt-4 grid gap-4 md:grid-cols-2">
            <x-form.location-field name="pickup" label="Pickup location" :id-prefix="$p" :value="$value('pickup')" :place-id="$value('pickup_place_id')"
                placeholder="e.g. Hotel name, street or Puducherry Railway Station" required :mode="$mode" marker="bg-brand-green-500" />
            <div data-dropoff-wrapper>
                <x-form.location-field name="dropoff" :label="$dropLabels[$currentType->value]" :id-prefix="$p" :value="$value('dropoff')" :place-id="$value('dropoff_place_id')"
                    :placeholder="$currentType->isOutstation() ? 'e.g. Chennai Central or Mahabalipuram' : 'e.g. Auroville Visitors Centre'"
                    :required="$currentType !== BookingType::Rental" :mode="$mode" marker="bg-brand-blue-600" />
            </div>
        </div>

        {{-- Rental: one of the chosen vehicle's approved packages --}}
        <div class="mt-5 grid gap-4 sm:grid-cols-2" data-type-section="rental" @if ($currentType !== BookingType::Rental) hidden @endif>
            <div data-package-wrapper>
                <label for="{{ $p }}-rental_package_id" class="form-label">Rental package <span class="text-red-600" aria-hidden="true">*</span></label>
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
                <p class="form-help">Packages shown are for the vehicle selected below.</p>
                <x-form.field-error name="rental_package_id" :id="$p.'-rental_package_id'" />
            </div>
            <details class="rounded-2xl border border-line bg-surface p-4 sm:col-span-2" @if ($selectedSights) open @endif>
                <summary class="cursor-pointer text-sm font-semibold text-brand-blue-900">Add places you would like to visit (optional)</summary>
                <div class="mt-3 grid gap-2 sm:grid-cols-2" id="{{ $p }}-sightseeing">
                    @foreach ($booking['destinations'] as $destination)
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-line bg-white px-3 py-2 text-sm font-medium has-checked:border-brand-green-600 has-checked:bg-brand-green-50">
                            <input type="checkbox" name="sightseeing[]" value="{{ $destination['slug'] }}" class="size-4 accent-brand-green-600" @checked(in_array($destination['slug'], $selectedSights, true))>
                            {{ $destination['name'] }}
                        </label>
                    @endforeach
                </div>
            </details>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div>
                <label for="{{ $p }}-travel_date" class="form-label"><span data-date-label>{{ $currentType->isOutstation() ? 'Departure date' : 'Pickup date' }}</span> <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="date" id="{{ $p }}-travel_date" name="travel_date" value="{{ $value('travel_date') }}" min="{{ $booking['minDate'] }}" max="{{ $booking['maxDate'] }}" required class="form-control" data-travel-date @error('travel_date') aria-invalid="true" @enderror>
                <x-form.field-error name="travel_date" :id="$p.'-travel_date'" />
            </div>
            <div>
                <label for="{{ $p }}-pickup_time" class="form-label">Pickup time <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="time" id="{{ $p }}-pickup_time" name="pickup_time" value="{{ $value('pickup_time') }}" step="300" required class="form-control" @error('pickup_time') aria-invalid="true" @enderror>
                <x-form.field-error name="pickup_time" :id="$p.'-pickup_time'" />
            </div>
            <div data-type-section="outstation_round_trip" @if ($currentType !== BookingType::OutstationRoundTrip) hidden @endif>
                <label for="{{ $p }}-return_date" class="form-label">Return date <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="date" id="{{ $p }}-return_date" name="return_date" value="{{ $value('return_date') }}" min="{{ $booking['minDate'] }}" max="{{ $booking['maxDate'] }}" class="form-control" data-return-date @error('return_date') aria-invalid="true" @enderror>
                <x-form.field-error name="return_date" :id="$p.'-return_date'" />
            </div>
            <div data-type-section="outstation_round_trip" @if ($currentType !== BookingType::OutstationRoundTrip) hidden @endif>
                <label for="{{ $p }}-return_time" class="form-label">Return time <span class="font-normal text-muted">(optional)</span></label>
                <input type="time" id="{{ $p }}-return_time" name="return_time" value="{{ $value('return_time') }}" step="300" class="form-control" @error('return_time') aria-invalid="true" @enderror>
                <x-form.field-error name="return_time" :id="$p.'-return_time'" />
            </div>
        </div>
    </fieldset>

    {{-- 3. Vehicle --}}
    <fieldset class="mt-8" id="{{ $p }}-vehicle_id">
        <legend class="booking-step"><span>3</span> Choose your vehicle</legend>
        {{-- Only vehicles with an approved fare for the selected trip type are shown; booking.js re-filters when the type changes. --}}
        <div class="mt-4 grid gap-3 sm:grid-cols-2 {{ $isPage ? 'xl:grid-cols-2' : 'lg:grid-cols-4' }}" data-vehicle-list>
            @foreach ($booking['vehicles'] as $vehicle)
                @php($option = $booking['vehicleOptions'][$vehicle->id][$currentType->value] ?? ['available' => false])
                <label class="vehicle-option group relative flex cursor-pointer flex-col overflow-hidden rounded-2xl border-2 border-line bg-white transition hover:border-brand-blue-200 has-checked:border-brand-green-600 has-checked:bg-brand-green-50/40 has-checked:shadow-lift has-focus-visible:ring-4 has-focus-visible:ring-brand-blue-100"
                    data-vehicle-option data-vehicle-id="{{ $vehicle->id }}" @if (! $option['available']) hidden @endif>
                    <input type="radio" name="vehicle_id" value="{{ $vehicle->id }}" class="sr-only" data-vehicle-input data-name="{{ $vehicle->name }}"
                        @checked($option['available'] && $currentVehicle === $vehicle->id) @disabled(! $option['available']) required>
                    <span class="flex h-28 items-center justify-center bg-gradient-to-br from-brand-blue-50 to-brand-green-50 px-6 pt-4 pb-2">
                        @if ($vehicle->imageUrl())
                            <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" class="h-20 w-auto max-w-full object-contain" loading="lazy" decoding="async">
                        @else
                            <x-vehicle-art :category="$vehicle->category" class="h-20 w-auto" />
                        @endif
                    </span>
                    <span class="flex flex-1 flex-col p-4">
                        <span class="flex items-baseline justify-between gap-2">
                            <span class="font-bold text-brand-blue-950">{{ $vehicle->name }}</span>
                            <span class="text-right text-sm font-bold whitespace-nowrap text-brand-green-700" data-vehicle-rate>{{ $option['rate'] ?? '' }}</span>
                        </span>
                        <span class="mt-0.5 text-xs font-semibold tracking-wide text-brand-blue-700 uppercase">{{ $vehicle->categoryLabel() }}</span>
                        @if ($vehicle->passenger_capacity || $vehicle->luggage_capacity)
                            <span class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted">
                                @if ($vehicle->passenger_capacity)<span class="flex items-center gap-1"><x-ui.icon name="users" class="size-3.5" /> {{ $vehicle->passenger_capacity }} {{ \Illuminate\Support\Str::plural('seat', $vehicle->passenger_capacity) }}</span>@endif
                                @if ($vehicle->luggage_capacity)<span class="flex items-center gap-1"><x-ui.icon name="sparkle" class="size-3.5" /> {{ $vehicle->luggage_capacity }} {{ \Illuminate\Support\Str::plural('bag', $vehicle->luggage_capacity) }}</span>@endif
                            </span>
                        @endif
                    </span>
                    <span class="absolute top-3 right-3 flex size-6 items-center justify-center rounded-full border-2 border-line bg-white group-has-checked:border-brand-green-600 group-has-checked:bg-brand-green-600" aria-hidden="true">
                        <x-ui.icon name="check" class="hidden size-4 text-white group-has-checked:block" />
                    </span>
                </label>
            @endforeach
        </div>
        {{-- Empty state for the selected trip type (booking.js keeps it in sync). --}}
        @php($noneForType = collect($booking['vehicleOptions'])->every(fn ($types) => ! ($types[$currentType->value]['available'] ?? false)))
        <div class="mt-4 flex gap-3 rounded-2xl bg-surface p-5 text-sm text-muted" data-vehicle-empty role="status" @unless ($noneForType) hidden @endunless>
            <x-ui.icon name="info" class="size-5 shrink-0 text-brand-blue-700" />
            <p>
                <span class="font-semibold text-brand-blue-950">No vehicles are available for <span data-vehicle-empty-type>{{ $currentType->label() }}</span> online booking right now.</span>
                Please try another trip type, or <a href="{{ route('contact') }}" class="font-semibold text-brand-blue-700 underline">contact us</a> to book.
            </p>
        </div>
        <p class="form-help" data-vehicle-message aria-live="polite"></p>
        <x-form.field-error name="vehicle_id" />
    </fieldset>

    {{-- Live estimate --}}
    <div class="mt-6 rounded-2xl bg-brand-blue-950 p-5 text-white sm:p-6" data-estimate aria-live="polite">
        <div class="flex items-center justify-between gap-3">
            <h3 class="flex items-center gap-2 font-bold"><x-ui.icon name="receipt" class="size-5 text-brand-green-200" /> Fare estimate</h3>
            <span class="hidden size-5 animate-spin rounded-full border-2 border-white/30 border-t-white" data-estimate-spinner aria-hidden="true"></span>
        </div>
        <div class="mt-2 text-sm text-white/85" data-estimate-body
            data-default="{{ $distanceEnabled ? 'Choose your trip and vehicle to see an estimate.' : 'The fare depends on your route and is confirmed by our team when you send the request.' }}">
            {{ $distanceEnabled ? 'Choose your trip and vehicle to see an estimate.' : 'The fare depends on your route and is confirmed by our team when you send the request.' }}
        </div>
        <p class="mt-3 text-xs text-white/60">An amount is shown only when the fare is approved and can be calculated for your trip. Every booking is a request: H2 CABS confirms availability and the final fare with you.</p>
    </div>

    {{-- 4. Passenger --}}
    <fieldset class="mt-8">
        <legend class="booking-step"><span>4</span> Passenger details</legend>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 {{ $isPage ? '' : 'lg:grid-cols-4' }}">
            <div>
                <label for="{{ $p }}-name" class="form-label">Your name <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="text" id="{{ $p }}-name" name="name" value="{{ $value('name') }}" required maxlength="80" autocomplete="name" class="form-control" @error('name') aria-invalid="true" @enderror>
                <x-form.field-error name="name" :id="$p.'-name'" />
            </div>
            <div>
                <label for="{{ $p }}-phone" class="form-label">Mobile number <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="tel" id="{{ $p }}-phone" name="phone" value="{{ $value('phone') }}" required minlength="10" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" autocomplete="tel-national" placeholder="9876543210"
                    data-phone-input aria-describedby="{{ $p }}-phone-help @error('phone') {{ $p }}-phone-error @enderror"title="Enter a 10-digit mobile number" class="form-control" @error('phone') aria-invalid="true" @enderror>
                <p id="{{ $p }}-phone-help" class="form-help">10 digits, numbers only — without +91 or 0.</p>
                <x-form.field-error name="phone" :id="$p.'-phone'" />
            </div>
            <div>
                <label for="{{ $p }}-passengers" class="form-label">Passengers <span class="text-red-600" aria-hidden="true">*</span></label>
                <select id="{{ $p }}-passengers" name="passengers" required class="form-control" @error('passengers') aria-invalid="true" @enderror>
                    @foreach (range(1, config('site.booking.max_passengers')) as $count)
                        <option value="{{ $count }}" @selected((int) $value('passengers', 1) === $count)>{{ $count }}</option>
                    @endforeach
                </select>
                <x-form.field-error name="passengers" :id="$p.'-passengers'" />
            </div>
            <div @class(['sm:col-span-2' => $isPage])>
                <label for="{{ $p }}-notes" class="form-label">Notes <span class="font-normal text-muted">(optional)</span></label>
                <input type="text" id="{{ $p }}-notes" name="notes" value="{{ $value('notes') }}" maxlength="500" placeholder="Luggage, stops, flight or train number…" class="form-control" @error('notes') aria-invalid="true" @enderror>
                <x-form.field-error name="notes" :id="$p.'-notes'" />
            </div>
        </div>
    </fieldset>

    <div class="mt-8 flex flex-col gap-4 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-md text-sm text-muted">
            @if ($booking['whatsappConfigured'])
                Next you review your request. It is sent only when you press <strong>Send</strong> in WhatsApp.
            @else
                Next you review your request. WhatsApp booking is being set up — you can share the summary by phone or email.
            @endif
        </p>
        <button type="submit" class="btn {{ $booking['whatsappConfigured'] ? 'btn-whatsapp' : 'btn-primary' }} px-8 text-base" data-submit disabled>
            @if ($booking['whatsappConfigured'])<x-ui.icon name="whatsapp" class="size-5" />@endif
            <span data-submit-label>{{ $booking['whatsappConfigured'] ? 'Review & book via WhatsApp' : 'Continue to booking review' }}</span>
        </button>
    </div>

    @if ($mode === 'unavailable' && app()->isLocal())
        <p class="mt-4 rounded-xl bg-surface p-3 font-mono text-xs text-muted">
            Developer note: Google location search is not configured, so bookings cannot be submitted. Set GOOGLE_MAPS_BROWSER_KEY (browser key) or GOOGLE_PLACES_API_KEY (server key) in .env — see docs/DEPLOYMENT.md.
        </p>
    @endif
</form>
