<section aria-labelledby="destinations-title" class="section">
    <div class="container-site">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div class="max-w-2xl">
                <p class="eyebrow">Popular destinations</p>
                <h2 id="destinations-title" class="heading-display mt-3 text-3xl text-brand-blue-950 sm:text-4xl">Discover Pondicherry with a local driver</h2>
                <p class="mt-4 text-lg text-muted">Sea views, French streets, golden domes and backwater beaches — we take you there and wait while you explore.</p>
            </div>
            <a href="{{ route('destinations') }}" class="btn btn-outline self-start md:self-auto">
                Explore destinations <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 lg:grid-rows-[repeat(2,minmax(18rem,auto))]">
            @foreach ($destinations as $destination)
                <x-home.destination-card :destination="$destination" :size="$loop->first ? 'large' : 'default'"
                    :class="$loop->first ? 'sm:col-span-2 lg:row-span-2' : ''" />
            @endforeach
        </div>
    </div>
</section>
