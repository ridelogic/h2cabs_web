{{--
    Quick-start booking widget. It does not submit a booking: it carries the
    trip type, pickup, destination and date to /booking, where the full
    request is completed and sent on WhatsApp.
--}}
@php
    $search = app(\App\Places\PlaceSearch::class)->available();
    $today = now(config('site.booking.timezone'))->toDateString();
@endphp

<form method="GET" action="{{ route('booking.create') }}" {{ $attributes->merge(['class' => 'rounded-[1.75rem] bg-white p-5 text-ink shadow-[0_30px_80px_-20px_rgb(6_26_61/0.55)] ring-1 ring-black/5 sm:p-7']) }} aria-labelledby="widget-title">
    <div class="flex items-center justify-between gap-3">
        <h2 id="widget-title" class="font-display text-2xl font-bold text-brand-blue-950">Plan your ride</h2>
        <span class="pill bg-brand-green-50 text-brand-green-700"><x-ui.icon name="whatsapp" class="size-3.5" /> Book via WhatsApp</span>
    </div>

    <fieldset class="mt-5">
        <legend class="form-label">Trip type</legend>
        <div class="grid grid-cols-2 gap-2">
            @foreach (\App\Enums\BookingType::cases() as $type)
                <label @class([
                    'flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-sm font-semibold text-brand-blue-900 transition hover:border-brand-blue-200 has-checked:border-brand-green-600 has-checked:bg-brand-green-50 has-checked:text-brand-green-800 has-focus-visible:ring-4 has-focus-visible:ring-brand-blue-100',
                    'col-span-2' => $loop->first,
                ])>
                    <input type="radio" name="type" value="{{ $type->value }}" class="sr-only" @checked($loop->first)>
                    <x-ui.icon :name="$type->icon()" class="size-4" />
                    {{ $type->label() }}
                </label>
            @endforeach
        </div>
    </fieldset>

    <div class="mt-4 grid gap-4">
        <x-form.location-field name="pickup" label="Pickup location" placeholder="Hotel, street or landmark" :search="$search" icon="pin" />
        <x-form.location-field name="dropoff" label="Destination" placeholder="Where are you going?" :search="$search" icon="target" />
        <div>
            <label for="widget-date" class="form-label">Travel date</label>
            <div class="relative">
                <x-ui.icon name="calendar" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-brand-blue-600" />
                <input type="date" id="widget-date" name="date" min="{{ $today }}" value="{{ $today }}" class="form-control pl-12">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary mt-6 w-full text-base">
        Continue booking <x-ui.icon name="arrow-right" class="size-5" />
    </button>
    <p class="mt-3 text-center text-xs text-muted">Next, add time, passengers and contact details.</p>
</form>
