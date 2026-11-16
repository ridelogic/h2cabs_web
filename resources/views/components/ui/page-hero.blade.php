{{--
    Inner-page hero: photo background, breadcrumb and the page's H1.
    The breadcrumb is also emitted as BreadcrumbList structured data.
--}}
@props(['photo', 'eyebrow' => null, 'title', 'crumb', 'compact' => false])

@php
    $breadcrumbs = [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => $crumb, 'url' => url()->current()],
    ];
@endphp

<section class="relative isolate overflow-hidden bg-brand-blue-950 text-white" aria-labelledby="page-title">
    <x-ui.photo :key="$photo" sizes="100vw" eager class="absolute inset-0 -z-10 opacity-60" />
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-r from-brand-blue-950/95 via-brand-blue-950/75 to-brand-blue-950/30"></div>

    <div @class(['container-site', 'py-16 sm:py-20 lg:py-24' => ! $compact, 'py-10 sm:py-14' => $compact])>
        <nav aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-2 text-sm text-white/70">
                <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-semibold text-white">{{ $crumb }}</li>
            </ol>
        </nav>

        <div class="mt-6 max-w-2xl">
            @if ($eyebrow)
                <p class="eyebrow !text-brand-green-200">{{ $eyebrow }}</p>
            @endif
            <h1 id="page-title" class="mt-3 font-display text-4xl leading-tight font-bold text-balance sm:text-5xl">{{ $title }}</h1>
            @if ($slot->isNotEmpty())
                <p class="mt-5 text-lg text-pretty text-white/85">{{ $slot }}</p>
            @endif
        </div>
    </div>
</section>

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($breadcrumbs)->map(fn ($crumb, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $crumb['name'],
            'item' => $crumb['url'],
        ])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush
