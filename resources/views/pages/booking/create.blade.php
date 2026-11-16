<x-layouts.app :seo="$seo">
    <x-ui.page-hero photo="highway" crumb="Booking" eyebrow="Book a ride" title="Book your H2 CABS taxi" compact>
        Local rides, rentals and outstation trips — choose your trip and vehicle, see an estimate, and send your request on WhatsApp.
    </x-ui.page-hero>

    <section class="section pt-10 sm:pt-14" aria-label="Booking form">
        <div class="container-site grid gap-8 lg:grid-cols-[1fr_20rem] lg:items-start xl:grid-cols-[1fr_22rem]">
            <div class="card p-5 sm:p-8">
                <x-booking.form id="booking-form" :booking="$booking" variant="page" :distance-enabled="$distanceEnabled" />
            </div>

            <aside class="space-y-5 lg:sticky lg:top-28" aria-label="Your trip">
                <div class="overflow-hidden rounded-(--radius-card) bg-brand-blue-900 text-white shadow-lift" data-trip-summary="booking-form">
                    <div class="flex items-center gap-3 border-b border-white/10 px-6 py-4">
                        <x-ui.icon name="car" class="size-6 text-brand-green-200" />
                        <h2 class="font-bold">Your trip</h2>
                    </div>
                    <dl class="space-y-3 px-6 py-5 text-sm">
                        @foreach (['type' => 'Trip', 'pickup' => 'Pickup', 'dropoff' => 'Drop / destination', 'date' => 'Date & time', 'vehicle' => 'Vehicle'] as $key => $label)
                            <div>
                                <dt class="text-xs tracking-wide text-white/60 uppercase">{{ $label }}</dt>
                                <dd class="mt-0.5 font-semibold break-words" data-summary="{{ $key }}">—</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="card p-6">
                    <h2 class="font-bold text-brand-blue-950">How it works</h2>
                    <ol class="mt-3 space-y-2.5 text-sm text-muted">
                        @foreach (app(\App\Content\HomePageContent::class)->bookingSteps() as $step)
                            <li class="flex gap-3">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-green-600 text-xs font-bold text-white">{{ $loop->iteration }}</span>
                                <span><strong class="text-ink">{{ $step['title'] }}.</strong> {{ $step['text'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                    @if (config('site.contact.phone'))
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', config('site.contact.phone')) }}" class="btn btn-outline mt-5 w-full">
                            <x-ui.icon name="phone" class="size-4" /> Prefer to call? {{ config('site.contact.phone') }}
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
