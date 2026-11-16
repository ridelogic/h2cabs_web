<x-layouts.app :seo="$seo">
    <x-ui.page-hero photo="rock-beach" crumb="Destinations" eyebrow="Local guide" title="Places to visit in and around Pondicherry">
        A short guide from the drivers who know the town — the seafront, the French Quarter, Auroville and the backwater beaches.
    </x-ui.page-hero>

    <section aria-labelledby="guide-title" class="section">
        <div class="container-site">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="eyebrow">Explore</p>
                    <h2 id="guide-title" class="heading-display mt-3 text-3xl text-brand-blue-950 sm:text-4xl">Pondicherry highlights</h2>
                    <p class="mt-4 text-lg text-muted">Pick the places you would like to see and we will plan the drive around them.</p>
                </div>

                <div role="group" aria-label="Filter destinations" class="flex flex-wrap gap-2" data-destination-filters hidden>
                    <button type="button" data-filter="all" aria-pressed="true" class="pill min-h-10 border border-line bg-white px-4 text-sm text-brand-blue-900 aria-pressed:border-brand-blue-700 aria-pressed:bg-brand-blue-700 aria-pressed:text-white">All</button>
                    @foreach ($categories as $key => $label)
                        <button type="button" data-filter="{{ $key }}" aria-pressed="false" class="pill min-h-10 border border-line bg-white px-4 text-sm text-brand-blue-900 aria-pressed:border-brand-blue-700 aria-pressed:bg-brand-blue-700 aria-pressed:text-white">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <p class="sr-only" aria-live="polite" data-destination-count></p>

            <div class="mt-12 space-y-8 lg:space-y-12">
                @foreach ($destinations as $destination)
                    <article data-category="{{ $destination['category'] }}" aria-labelledby="dest-{{ $destination['slug'] }}"
                        class="card grid overflow-hidden lg:grid-cols-2">
                        <div @class(['aspect-[3/2] overflow-hidden lg:aspect-auto lg:min-h-96', 'lg:order-last' => $loop->even])>
                            <x-ui.photo :key="$destination['photo']" sizes="(min-width: 1024px) 50vw, 100vw" />
                        </div>
                        <div class="flex flex-col justify-center p-7 sm:p-10 lg:p-12">
                            <p class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="pill bg-brand-green-50 text-brand-green-700">{{ $categories[$destination['category']] }}</span>
                                <span class="flex items-center gap-1 text-muted"><x-ui.icon name="pin" class="size-4" />{{ $destination['area'] }}</span>
                            </p>
                            <h3 id="dest-{{ $destination['slug'] }}" class="heading-display mt-4 text-3xl text-brand-blue-950">{{ $destination['name'] }}</h3>
                            <p class="mt-4 text-lg text-muted">{{ $destination['intro'] }}</p>
                            <p class="mt-5 flex gap-3 rounded-2xl bg-sand p-4 text-sm text-ink">
                                <x-ui.icon name="info" class="size-5 text-brand-blue-600" />
                                <span><strong>Driver's tip:</strong> {{ $destination['tip'] }}</span>
                            </p>
                            <div class="mt-7">
                                <a href="{{ route('booking.create', ['destination' => $destination['slug']]) }}" class="btn btn-primary">
                                    Plan a trip<span class="sr-only"> to {{ $destination['name'] }}</span>
                                    <x-ui.icon name="arrow-right" class="size-4" />
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section aria-labelledby="plans-title" class="section bg-sand">
        <div class="container-site">
            <x-ui.section-heading id="plans-title" eyebrow="Day plans" title="Combine places in one trip">
                Popular combinations our drivers suggest. Tell us what you would like to change — the plan is yours.
            </x-ui.section-heading>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($itineraries as $plan)
                    <article class="card flex flex-col p-7">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-brand-blue-700 text-white"><x-ui.icon name="compass" /></span>
                        <h3 class="mt-5 text-xl font-bold text-brand-blue-950">{{ $plan['title'] }}</h3>
                        <p class="mt-2 text-muted">{{ $plan['text'] }}</p>
                        <ol class="mt-5 flex-1 space-y-2 border-l-2 border-brand-green-200 pl-4 text-sm font-medium text-ink">
                            @foreach ($plan['stops'] as $stop)
                                <li>{{ $catalog->find($stop)['name'] }}</li>
                            @endforeach
                        </ol>
                        <a href="{{ route('booking.create', ['type' => 'rental', 'destination' => implode(',', $plan['stops'])]) }}" class="mt-7 inline-flex items-center gap-2 font-semibold text-brand-green-700 hover:text-brand-green-800">
                            Book this plan<span class="sr-only">: {{ $plan['title'] }}</span> <x-ui.icon name="arrow-right" class="size-4" />
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-ui.cta-band title="Want a custom sightseeing plan?" text="Tell us what you enjoy and how much time you have — we will suggest a route." />
</x-layouts.app>
