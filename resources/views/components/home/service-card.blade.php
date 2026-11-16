{{-- Service card with photo, summary and a "Book Now" link that preselects the trip type. --}}
@props(['service', 'detailed' => false, 'headingLevel' => 'h3'])

<article class="card group flex flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-lift">
    <div class="relative aspect-[3/2] overflow-hidden">
        <x-ui.photo :key="$service['photo']" class="transition duration-500 group-hover:scale-105" />
        <span class="absolute bottom-4 left-4 flex size-12 items-center justify-center rounded-2xl bg-white text-brand-blue-700 shadow-lg">
            <x-ui.icon :name="$service['icon']" />
        </span>
    </div>
    <div class="flex flex-1 flex-col p-6">
        <{{ $headingLevel }} class="text-xl font-bold text-brand-blue-950">{{ $service['title'] }}</{{ $headingLevel }}>
        <p class="mt-2 text-muted">{{ $service['summary'] }}</p>

        @if ($detailed)
            <ul class="mt-4 space-y-2 text-sm text-ink">
                @foreach ($service['points'] as $point)
                    <li class="flex gap-2">
                        <x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-600" />
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto pt-6">
            <a href="{{ route('booking.create', \App\Content\ServiceCatalog::bookingParams($service)) }}" class="inline-flex items-center gap-2 font-semibold text-brand-green-700 hover:text-brand-green-800">
                Book Now<span class="sr-only">: {{ $service['title'] }}</span>
                <x-ui.icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
            </a>
        </div>
    </div>
</article>
