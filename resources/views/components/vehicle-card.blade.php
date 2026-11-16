{{--
    Public vehicle card with the per-km rate for each booking type that is
    in effect. Rates that are not yet approved are never shown; a vehicle
    with no bookable fare shows "Fare available on request" instead.
--}}
@props(['vehicle'])

@php
    use App\Enums\BookingType;
    use App\Fares\Money;
    $rates = collect(BookingType::cases())->map(fn (BookingType $type) => [$type, $vehicle->fareFor($type)])->filter(fn ($pair) => $pair[1]);
@endphp

<article class="card flex flex-col overflow-hidden">
    <div class="relative flex aspect-[16/9] items-center justify-center overflow-hidden bg-gradient-to-br from-brand-blue-800 to-brand-green-700">
        @if ($vehicle->imageUrl())
            <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" loading="lazy" decoding="async" class="h-full w-full object-cover">
        @else
            <x-ui.icon name="car" class="size-20 text-white/90" />
        @endif
        <span class="pill absolute top-4 left-4 bg-white/95 text-brand-blue-800">{{ $vehicle->categoryLabel() }}</span>
    </div>
    <div class="flex flex-1 flex-col p-6">
        <h3 class="text-xl font-bold text-brand-blue-950">{{ $vehicle->name }}</h3>
        @if ($vehicle->passenger_capacity || $vehicle->luggage_capacity)
            <p class="mt-1 flex flex-wrap gap-x-4 text-sm text-muted">
                @if ($vehicle->passenger_capacity)<span class="flex items-center gap-1"><x-ui.icon name="users" class="size-4" /> Up to {{ $vehicle->passenger_capacity }} passengers</span>@endif
                @if ($vehicle->luggage_capacity)<span class="flex items-center gap-1"><x-ui.icon name="sparkle" class="size-4" /> {{ $vehicle->luggage_capacity }} bags</span>@endif
            </p>
        @endif
        @if ($vehicle->description)<p class="mt-3 text-muted">{{ $vehicle->description }}</p>@endif

        @if ($rates->isNotEmpty())
            <dl class="mt-5 divide-y divide-line rounded-2xl border border-line text-sm">
                @foreach ($rates as [$type, $fare])
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-muted">{{ $type->label() }}</dt>
                        <dd class="font-bold text-brand-blue-950">{{ $fare->publicRateLabel() }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-auto pt-6">
                <a href="{{ route('booking.create', ['vehicle' => $vehicle->slug]) }}" class="btn btn-primary w-full">
                    Choose {{ $vehicle->name }} <x-ui.icon name="arrow-right" class="size-4" />
                </a>
            </div>
        @else
            {{-- No bookable fare yet: shown, but not selectable for online booking. --}}
            <p class="mt-5 rounded-2xl border border-line px-4 py-2.5 text-sm font-bold text-brand-blue-950">Fare available on request</p>

            <div class="mt-auto pt-6">
                <a href="{{ route('contact') }}" class="btn btn-outline w-full">Enquire about {{ $vehicle->name }}</a>
            </div>
        @endif
    </div>
</article>
