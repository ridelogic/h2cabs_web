{{-- Photo-led destination card linking to the booking form with the destination preselected. --}}
@props(['destination', 'size' => 'default'])

<article {{ $attributes->class(['group relative isolate flex overflow-hidden rounded-(--radius-card) bg-brand-blue-950 shadow-card', 'min-h-80' => $size === 'default', 'min-h-80 lg:min-h-full' => $size === 'large']) }}>
    <x-ui.photo :key="$destination['photo']" class="absolute inset-0 -z-10 transition duration-700 group-hover:scale-105"
        :sizes="$size === 'large' ? '(min-width: 1024px) 50vw, 100vw' : '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw'" />
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-brand-blue-950/95 via-brand-blue-950/35 to-transparent"></div>

    <div class="mt-auto w-full p-6 text-white">
        <p class="flex items-center gap-1.5 text-sm font-medium text-brand-green-200">
            <x-ui.icon name="pin" class="size-4" />
            {{ $destination['area'] }}
        </p>
        <h3 class="mt-1 font-display text-2xl font-bold">{{ $destination['name'] }}</h3>
        @if ($size === 'large')
            <p class="mt-2 max-w-md text-white/85">{{ $destination['intro'] }}</p>
        @endif
        <a href="{{ route('booking.create', ['destination' => $destination['slug']]) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-white after:absolute after:inset-0 hover:underline">
            Plan a trip<span class="sr-only"> to {{ $destination['name'] }}</span>
            <x-ui.icon name="arrow-right" class="size-4" />
        </a>
    </div>
</article>
