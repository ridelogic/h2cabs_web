<x-layouts.app :seo="$seo">
    <x-ui.page-hero photo="aurobindo-ashram" crumb="Contact Us" eyebrow="Contact" title="Get in touch with H2 CABS">
        Book a ride, ask about a trip or talk to us about working together.
    </x-ui.page-hero>

    <section aria-labelledby="contact-title" class="section">
        <div class="container-site grid gap-10 lg:grid-cols-[1.2fr_1fr]">
            <div class="rounded-[2rem] bg-brand-blue-900 p-6 text-white sm:p-10">
                <h2 id="contact-title" class="heading-display text-3xl">Contact details</h2>
                <p class="mt-3 text-white/80">The quickest way to book is the online form, which sends your trip details to us on WhatsApp.</p>
                <x-site.contact-channels class="mt-8" />
            </div>

            <div class="space-y-6">
                <article class="card p-7">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-green-100 text-brand-green-700"><x-ui.icon name="calendar" /></span>
                    <h2 class="mt-5 text-xl font-bold text-brand-blue-950">Book a ride</h2>
                    <p class="mt-2 text-muted">Local trips, airport transfers, outstation journeys and sightseeing.</p>
                    <a href="{{ route('booking.create') }}" class="btn btn-primary mt-5">Open booking form <x-ui.icon name="arrow-right" class="size-4" /></a>
                </article>
                <article class="card p-7">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-blue-50 text-brand-blue-700"><x-ui.icon name="handshake" /></span>
                    <h2 class="mt-5 text-xl font-bold text-brand-blue-950">Hotels, travel desks & businesses</h2>
                    <p class="mt-2 text-muted">Arrange transport for your guests, clients or events. Reach us using the details on this page.</p>
                </article>
                <article class="card p-7">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-blue-50 text-brand-blue-700"><x-ui.icon name="steering" /></span>
                    <h2 class="mt-5 text-xl font-bold text-brand-blue-950">Drivers</h2>
                    <p class="mt-2 text-muted">Interested in the Helping Hand Drivers Association? <a href="{{ route('about') }}" class="font-semibold text-brand-blue-700 hover:underline">Learn about the association</a>.</p>
                </article>
            </div>
        </div>
    </section>
</x-layouts.app>
