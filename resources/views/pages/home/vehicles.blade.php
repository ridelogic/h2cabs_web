@use('App\Enums\BookingType')

<section aria-labelledby="vehicles-title" class="section">
    <div class="container-site" data-carousel>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-2xl">
                <p class="eyebrow">Our vehicles</p>
                <h2 id="vehicles-title" class="heading-display mt-3 text-3xl text-brand-blue-950 sm:text-4xl">Choose the right car for your trip</h2>
            </div>
            @if ($vehicles->count() > 1)
                <div class="flex gap-2">
                    <button type="button" data-carousel-prev class="flex size-12 items-center justify-center rounded-full border border-line bg-white text-brand-blue-800 transition hover:bg-brand-blue-50 disabled:opacity-40" aria-label="Previous vehicles">
                        <x-ui.icon name="arrow-right" class="size-5 rotate-180" />
                    </button>
                    <button type="button" data-carousel-next class="flex size-12 items-center justify-center rounded-full border border-line bg-white text-brand-blue-800 transition hover:bg-brand-blue-50 disabled:opacity-40" aria-label="Next vehicles">
                        <x-ui.icon name="arrow-right" class="size-5" />
                    </button>
                </div>
            @endif
        </div>

        @if ($vehicles->isNotEmpty())
            <ul data-carousel-track tabindex="0" aria-label="Vehicle models — use the arrow keys to scroll"
                class="-mx-4 mt-10 flex snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth px-4 pb-6 [scrollbar-width:thin] focus-visible:outline-2 focus-visible:outline-brand-green-500">
                @foreach ($vehicles as $vehicle)
                    @php
                        $rates = collect(BookingType::cases())->map(fn ($type) => [$type, $booking['vehicleOptions'][$vehicle->id][$type->value] ?? null])
                            ->filter(fn ($pair) => $pair[1]['available'] ?? false);
                    @endphp
                    <li data-carousel-item class="w-[85%] shrink-0 snap-start sm:w-[22rem]">
                        <article class="card group flex h-full flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-lift">
                            <div class="relative flex h-44 items-center justify-center bg-gradient-to-br from-brand-blue-700 via-brand-blue-600 to-brand-green-600 px-8">
                                <span class="pill absolute top-4 left-4 bg-white/95 text-brand-blue-800">{{ $vehicle->categoryLabel() }}</span>
                                @if ($vehicle->imageUrl())
                                    <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" loading="lazy" class="h-32 w-auto object-contain drop-shadow-xl">
                                @else
                                    <x-vehicle-art :category="$vehicle->category" :label="$vehicle->categoryLabel().' illustration'" class="w-full max-w-64 drop-shadow-xl transition duration-500 group-hover:translate-x-1" />
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                <h3 class="text-xl font-bold text-brand-blue-950">{{ $vehicle->name }}</h3>
                                @if ($vehicle->description)<p class="mt-1 text-sm text-muted">{{ $vehicle->description }}</p>@endif
                                <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
                                    @if ($vehicle->passenger_capacity)<span class="flex items-center gap-1"><x-ui.icon name="users" class="size-4" /> {{ $vehicle->passenger_capacity }} passengers</span>@endif
                                    @if ($vehicle->luggage_capacity)<span class="flex items-center gap-1"><x-ui.icon name="sparkle" class="size-4" /> {{ $vehicle->luggage_capacity }} bags</span>@endif
                                </p>
                                @if ($rates->isNotEmpty())
                                    <dl class="mt-4 space-y-1.5 border-t border-line pt-4 text-sm">
                                        @foreach ($rates as [$type, $option])
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-muted">{{ $type->label() }}</dt>
                                                <dd class="font-semibold text-brand-blue-950">{{ $option['rate'] }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                    <div class="mt-auto pt-6">
                                        <a href="{{ route('booking.create', ['vehicle' => $vehicle->slug]) }}" data-select-vehicle="{{ $vehicle->id }}" class="btn btn-primary w-full">
                                            Select {{ $vehicle->name }}
                                        </a>
                                    </div>
                                @else
                                    {{-- No bookable fare yet: shown, but not selectable for online booking. --}}
                                    <p class="mt-4 border-t border-line pt-4 text-sm font-semibold text-brand-blue-950">Fare available on request</p>
                                    <div class="mt-auto pt-6">
                                        <a href="{{ route('contact') }}" class="btn btn-outline w-full">Enquire about {{ $vehicle->name }}</a>
                                    </div>
                                @endif
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-10 rounded-2xl bg-surface p-8 text-center text-muted">Vehicle options are being set up. Please <a href="{{ route('contact') }}" class="font-semibold text-brand-blue-700 underline">contact us</a> to book.</p>
        @endif
    </div>
</section>
