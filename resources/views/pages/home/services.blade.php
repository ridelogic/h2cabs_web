<section aria-labelledby="services-title" class="section bg-surface">
    <div class="container-site">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div class="max-w-2xl">
                <p class="eyebrow">Our services</p>
                <h2 id="services-title" class="heading-display mt-3 text-3xl text-brand-blue-950 sm:text-4xl">Four ways to travel with H2 CABS</h2>
                <p class="mt-4 text-lg text-muted">Each is booked and priced on its own — pick the one that fits your trip.</p>
            </div>
            <a href="{{ route('services') }}" class="btn btn-outline self-start md:self-auto">
                All service details <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($services as $service)
                <x-home.service-card :service="$service" />
            @endforeach
        </div>
    </div>
</section>
