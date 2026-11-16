<section aria-labelledby="outstation-title" class="section overflow-hidden">
    <div class="container-site grid gap-12 lg:grid-cols-[1fr_1.05fr] lg:gap-16">
        <div>
            <p class="eyebrow">Outstation</p>
            <h2 id="outstation-title" class="heading-display mt-3 text-3xl text-brand-blue-950 sm:text-4xl lg:text-5xl">Travel beyond Puducherry, one way or round trip</h2>
            <p class="mt-5 text-lg text-muted">
                Book a one-way drop to another city, or a round trip with the same car for the return. Tell us the pickup
                and destination, choose a vehicle, and we show an estimate where we can — the final fare is confirmed before you travel.
            </p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <a href="{{ route('booking.create', ['type' => 'outstation_one_way']) }}" class="card group flex flex-col p-6 transition hover:-translate-y-1 hover:shadow-lift">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-green-600 text-white"><x-ui.icon name="arrow-right" /></span>
                    <span class="mt-4 text-lg font-bold text-brand-blue-950">Outstation One Way</span>
                    <span class="mt-1 text-sm text-muted">Drop at another city; no return leg to arrange.</span>
                    <span class="mt-4 text-sm font-semibold text-brand-green-700 group-hover:underline">Book one way →</span>
                </a>
                <a href="{{ route('booking.create', ['type' => 'outstation_round_trip']) }}" class="card group flex flex-col p-6 transition hover:-translate-y-1 hover:shadow-lift">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-navy text-white"><x-ui.icon name="repeat" /></span>
                    <span class="mt-4 text-lg font-bold text-brand-blue-950">Outstation Round Trip</span>
                    <span class="mt-1 text-sm text-muted">Departure and return dates; same car throughout.</span>
                    <span class="mt-4 text-sm font-semibold text-brand-green-700 group-hover:underline">Book round trip →</span>
                </a>
            </div>

            <h3 class="mt-10 text-sm font-bold tracking-wide text-brand-blue-800 uppercase">Example destinations from Puducherry</h3>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($routes as $city)
                    <li>
                        <a href="{{ route('booking.create', ['type' => 'outstation_one_way', 'dropoff' => $city]) }}" class="pill border border-line bg-white px-4 py-2 text-sm text-brand-blue-900 hover:border-brand-green-600">
                            <x-ui.icon name="pin" class="size-4 text-brand-green-600" /> {{ $city }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-muted">Examples only — other destinations on request. Availability is confirmed when you book.</p>
        </div>

        <div class="min-w-0 rounded-[2rem] bg-sand p-6 sm:p-8">
            <h3 class="flex items-center gap-2 text-xl font-bold text-brand-blue-950"><x-ui.icon name="receipt" class="text-brand-green-600" /> What affects the fare</h3>
            <x-outstation.fare-facts class="mt-5" />
            <a href="{{ route('services') }}#outstation" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-blue-700 hover:underline">
                How outstation booking works <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </div>
</section>
