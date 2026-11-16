@php
    // Header links (config site.header_navigation), then any published custom pages set to "Main navigation".
    $navigation = collect(config('site.header_navigation'))
        ->map(fn (array $item) => $item + ['url' => route($item['route'], $item['params'] ?? [])])
        ->merge(\App\Models\Page::navigation('header'))
        ->all();
    $whatsappUrl = \App\Support\WhatsApp::chatUrl('Hello H2 CABS, I would like to book a ride.');
    $isActive = function (array $item) {
        if (! isset($item['route'])) {
            return request()->is($item['path']);
        }
        if (! request()->routeIs($item['active'])) {
            return false;
        }

        // Trip-type links (Local Taxi, Rental, Outstation) are active only for their own type.
        return ! isset($item['type']) || in_array(request()->query('type'), (array) $item['type'], true);
    };
    // On the homepage the header sits transparently over the green hero until the page scrolls.
    $overlay = request()->routeIs('home');
@endphp

<header data-site-header @if ($overlay) data-overlay @endif class="sticky top-0 z-50 border-b border-line/70 bg-white/95 backdrop-blur-md transition-[background-color,box-shadow,border-color] duration-300">
    <div @class([
        'flex h-18 items-center justify-between gap-4 sm:h-20',
        'container-site' => ! $overlay,
        'mx-auto w-full px-4 sm:px-6 lg:h-24 lg:px-[5vw]' => $overlay,
    ])>
        {{-- One logo; over the homepage hero its text turns light (see .header-logo in app.css). --}}
        <x-ui.logo class="header-logo shrink-0" />

        <nav aria-label="Main" class="hidden xl:block">
            <ul class="flex items-center gap-1">
                @foreach ($navigation as $item)
                    <li>
                        <a href="{{ $item['url'] }}" @if ($isActive($item)) aria-current="page" @endif
                            @class(['nav-link', 'nav-link-active' => $isActive($item)])>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-2">
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-whatsapp hidden !px-4 sm:inline-flex">
                    <x-ui.icon name="whatsapp" class="size-5" />
                    <span class="sr-only 2xl:not-sr-only">WhatsApp</span>
                </a>
            @endif
            <a href="{{ route('booking.create') }}" @class(['header-cta btn btn-primary hidden sm:inline-flex', 'ring-2 ring-brand-green-200' => request()->routeIs('booking.*')])>
                Book a Taxi <x-ui.icon name="arrow-right" class="size-4" />
            </a>

            <button type="button" data-nav-toggle aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu"
                class="header-toggle group inline-flex size-11 items-center justify-center rounded-full border border-line text-brand-blue-800 hover:bg-brand-blue-50 xl:hidden">
                <x-ui.icon name="menu" class="group-aria-expanded:hidden" />
                <x-ui.icon name="close" class="hidden group-aria-expanded:block" />
            </button>
        </div>
    </div>

    <div id="mobile-menu" hidden class="max-h-[calc(100dvh-4.5rem)] overflow-y-auto border-t border-line bg-white xl:hidden">
        <nav aria-label="Mobile" class="container-site py-4">
            <ul class="flex flex-col gap-1">
                @foreach ($navigation as $item)
                    <li>
                        <a href="{{ $item['url'] }}" @if ($isActive($item)) aria-current="page" @endif
                            @class([
                                'flex min-h-12 items-center rounded-xl px-3 text-base font-semibold',
                                'bg-brand-blue-50 text-brand-blue-800' => $isActive($item),
                                'text-brand-blue-950 hover:bg-brand-blue-50' => ! $isActive($item),
                            ])>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <a href="{{ route('booking.create') }}" class="btn btn-primary w-full">Book a Taxi</a>
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-whatsapp w-full">
                        <x-ui.icon name="whatsapp" class="size-5" /> WhatsApp
                    </a>
                @endif
                @if ($phone = config('site.contact.phone'))
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="btn btn-outline w-full">
                        <x-ui.icon name="phone" class="size-4" /> Call {{ $phone }}
                    </a>
                @endif
            </div>
        </nav>
    </div>
</header>
