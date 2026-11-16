<section aria-labelledby="association-title" class="section overflow-hidden bg-sand">
    <div class="container-site grid items-center gap-14 lg:grid-cols-2 lg:gap-20">
        <div class="relative order-last lg:order-first">
            <div class="grid grid-cols-5 gap-4">
                <div class="col-span-3 aspect-[4/5] overflow-hidden rounded-(--radius-card) shadow-lift">
                    <x-ui.photo key="white-town" sizes="(min-width: 1024px) 30vw, 60vw" />
                </div>
                <div class="col-span-2 mt-16 aspect-[3/4] overflow-hidden rounded-(--radius-card) shadow-lift">
                    <x-ui.photo key="colonial-windows" sizes="(min-width: 1024px) 20vw, 40vw" />
                </div>
            </div>
            <div class="absolute -bottom-6 left-4 right-4 flex items-center gap-4 rounded-2xl bg-white p-5 shadow-lift sm:right-auto sm:max-w-sm">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-blue-700 text-white"><x-ui.icon name="heart" /></span>
                <p class="font-semibold text-brand-blue-950">Drivers supporting drivers — and every passenger they carry.</p>
            </div>
        </div>

        <div>
            <p class="eyebrow">The association</p>
            <h2 id="association-title" class="heading-display mt-3 text-3xl text-brand-blue-950 sm:text-4xl lg:text-5xl">
                Run by the Helping Hand Drivers Association
            </h2>
            <p class="mt-5 text-lg text-muted">
                The association was formed so that local drivers could stand together — sharing work, standards and a
                helping hand when it is needed. H2 CABS is how the members serve passengers under one name.
            </p>
            <ul class="mt-8 space-y-4">
                @foreach (['Members live and work in Puducherry', 'Common standards for courtesy and safety', 'A single, recognisable service for passengers'] as $point)
                    <li class="flex items-start gap-3 text-brand-blue-950">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-green-600 text-white"><x-ui.icon name="check" class="size-4" /></span>
                        <span class="font-medium">{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('about') }}" class="btn btn-secondary mt-10">
                Meet the association <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </div>
</section>
