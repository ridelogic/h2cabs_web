<x-layouts.app :seo="$seo">
    <x-ui.page-hero photo="highway" crumb="Our Services" eyebrow="Our services" title="Taxi services in and around Pondicherry">
        Local rides, hourly rentals and outstation trips — one way or round trip — in a Sedan or SUV.
    </x-ui.page-hero>

    <section id="types" aria-labelledby="types-title" class="section scroll-mt-24">
        <div class="container-site">
            <x-ui.section-heading id="types-title" eyebrow="Choose your trip" title="Four booking types, each priced on its own">
                Local and Rental are separate services, and outstation trips have their own one-way and round-trip fares.
            </x-ui.section-heading>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($primary as $service)
                    <x-home.service-card :service="$service" detailed />
                @endforeach
            </div>
        </div>
    </section>

    @if ($vehicles->isNotEmpty())
        <section id="vehicles" aria-labelledby="vehicles-title" class="section bg-sand">
            <div class="container-site">
                <x-ui.section-heading id="vehicles-title" eyebrow="Our vehicles" title="Pick the right car for your group">
                    Rates shown per booking type. Tolls and parking are extra unless stated, and the final fare is confirmed when you book.
                </x-ui.section-heading>

                <div class="mx-auto mt-12 grid max-w-5xl gap-6 md:grid-cols-2">
                    @foreach ($vehicles as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="outstation" aria-labelledby="outstation-title" class="section scroll-mt-24">
        <div class="container-site">
            <x-ui.section-heading id="outstation-title" eyebrow="Outstation" title="Outstation trips from Puducherry">
                One-way drops and round trips to other towns and cities, with the fare rules explained up front.
            </x-ui.section-heading>

            <div class="mt-12 grid gap-6 md:grid-cols-2">
                <article class="card p-7">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-green-600 text-white"><x-ui.icon name="arrow-right" /></span>
                    <h3 class="mt-5 text-xl font-bold text-brand-blue-950">Outstation One Way</h3>
                    <ul class="mt-3 space-y-2 text-muted">
                        <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-600" />Pickup in Puducherry, drop at your destination city or address.</li>
                        <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-600" />Billed on the one-way road distance at the vehicle's one-way rate.</li>
                        <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-600" />Choose your travel date, pickup time and vehicle.</li>
                    </ul>
                    <a href="{{ route('booking.create', ['type' => 'outstation_one_way']) }}" class="btn btn-primary mt-6">Book one way</a>
                </article>
                <article class="card p-7">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-navy text-white"><x-ui.icon name="repeat" /></span>
                    <h3 class="mt-5 text-xl font-bold text-brand-blue-950">Outstation Round Trip</h3>
                    <ul class="mt-3 space-y-2 text-muted">
                        <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-600" />The same car takes you there and brings you back.</li>
                        <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-600" />Add your departure and return dates, and a return time if you know it.</li>
                        <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-600" />Priced with its own round-trip rate and return-journey rule.</li>
                    </ul>
                    <a href="{{ route('booking.create', ['type' => 'outstation_round_trip']) }}" class="btn btn-primary mt-6">Book round trip</a>
                </article>
            </div>

            <div class="mt-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="min-w-0 rounded-[2rem] bg-sand p-6 sm:p-8">
                    <h3 class="flex items-center gap-2 text-xl font-bold text-brand-blue-950"><x-ui.icon name="receipt" class="text-brand-green-600" /> Fares and what affects them</h3>
                    <x-outstation.fare-facts class="mt-5" detailed />
                    <p class="mt-5 rounded-xl bg-white p-4 text-sm text-muted">
                        <strong class="text-ink">Estimate or confirmed price?</strong> When road distance is available we show an estimate:
                        billable km (the larger of the route distance and the minimum) × the rate, plus any configured charges.
                        Every amount is an estimate until our team confirms it on WhatsApp.
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-brand-blue-950">How outstation booking works</h3>
                    <ol class="mt-5 space-y-4">
                        @foreach ([
                            ['Choose one way or round trip', 'Pick the booking type on the booking page.'],
                            ['Enter pickup and destination', 'Choose from suggestions where available, or type the address.'],
                            ['Pick dates and a vehicle', 'Departure date and time, the return date for round trips, and Sedan or SUV.'],
                            ['Review the estimate', 'Shown when distance is available; otherwise we confirm the fare with you.'],
                            ['Send on WhatsApp', 'We prepare the message — you press Send, and our team confirms.'],
                        ] as [$title, $text])
                            <li class="flex gap-4">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-blue-700 font-bold text-white">{{ $loop->iteration }}</span>
                                <span><strong class="block text-brand-blue-950">{{ $title }}</strong><span class="text-muted">{{ $text }}</span></span>
                            </li>
                        @endforeach
                    </ol>

                    <h4 class="mt-8 text-sm font-bold tracking-wide text-brand-blue-800 uppercase">Example destinations</h4>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($routes as $city)
                            <li>
                                <a href="{{ route('booking.create', ['type' => 'outstation_one_way', 'dropoff' => $city]) }}" class="pill border border-line bg-white px-4 py-2 text-sm text-brand-blue-900 hover:border-brand-green-600">
                                    Puducherry → {{ $city }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-muted">Examples only — other destinations on request. No fixed prices or travel times are implied.</p>
                </div>
            </div>
        </div>
    </section>

    <section aria-labelledby="uses-title" class="section bg-surface">
        <div class="container-site">
            <x-ui.section-heading id="uses-title" eyebrow="Popular uses" title="Sightseeing, transfers and events" />
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($uses as $service)
                    <x-home.service-card :service="$service" detailed />
                @endforeach
            </div>
        </div>
    </section>

    <section aria-labelledby="how-title" class="section">
        <div class="container-site">
            <x-ui.section-heading id="how-title" eyebrow="How booking works" title="From request to ride" />
            <x-ui.booking-steps :steps="$steps" class="mt-12" />
            <p class="mx-auto mt-10 max-w-2xl text-center text-muted">
                Looking for ideas? See our <a href="{{ route('destinations') }}" class="font-semibold text-brand-blue-700 underline-offset-2 hover:underline">guide to Pondicherry destinations</a>
                or read <a href="{{ route('about') }}" class="font-semibold text-brand-blue-700 underline-offset-2 hover:underline">about the association</a>.
            </p>
        </div>
    </section>

    <x-ui.cta-band />
</x-layouts.app>
