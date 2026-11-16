{{--
    App / partner promotion card. Store links are only rendered when a
    real URL is configured; otherwise a "Coming soon" label is shown.
--}}
@props(['icon', 'eyebrow', 'title', 'points' => [], 'links' => [], 'tone' => 'blue', 'cta' => null])

@php
    $tones = [
        'blue' => 'from-brand-blue-700 to-brand-blue-900',
        'green' => 'from-brand-green-600 to-brand-green-800',
        'dark' => 'from-brand-blue-950 to-brand-blue-800',
    ];
    $stores = ['android' => 'Google Play', 'ios' => 'App Store'];
    $available = array_filter($links);
@endphp

<article class="relative flex flex-col overflow-hidden rounded-(--radius-card) bg-gradient-to-br {{ $tones[$tone] }} p-7 text-white shadow-lift sm:p-8">
    <div aria-hidden="true" class="pointer-events-none absolute -top-16 -right-16 size-48 rounded-full bg-white/10"></div>

    <span class="flex size-12 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
        <x-ui.icon :name="$icon" />
    </span>
    <p class="mt-5 text-sm font-semibold tracking-wide text-white/75 uppercase">{{ $eyebrow }}</p>
    <h3 class="mt-1 text-2xl font-extrabold">{{ $title }}</h3>
    <p class="mt-3 text-white/85">{{ $slot }}</p>

    <ul class="mt-5 flex-1 space-y-2 text-sm">
        @foreach ($points as $point)
            <li class="flex gap-2">
                <x-ui.icon name="check" class="mt-0.5 size-4 text-brand-green-200" />
                {{ $point }}
            </li>
        @endforeach
    </ul>

    <div class="mt-7 flex flex-wrap items-center gap-3">
        @if ($cta)
            <a href="{{ $cta['href'] }}" class="btn btn-light">
                {{ $cta['label'] }}
                <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        @elseif ($available)
            @foreach ($available as $platform => $url)
                <a href="{{ $url }}" rel="noopener" target="_blank" class="btn btn-light">
                    <x-ui.icon name="smartphone" class="size-4" />
                    Get it on {{ $stores[$platform] }}
                </a>
            @endforeach
        @else
            <span class="pill bg-white/15 px-4 py-2 text-sm ring-1 ring-white/30">
                <x-ui.icon name="clock" class="size-4" />
                Coming soon
            </span>
        @endif
    </div>
</article>
