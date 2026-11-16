@php
    $contact = config('site.contact');
    $social = array_filter(config('site.social'));
    $whatsappUrl = \App\Support\WhatsApp::chatUrl();
@endphp

<footer class="brand-gradient brand-arc text-white/80">
    <div class="container-site relative grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr] lg:py-16">
        <div class="sm:col-span-2 lg:col-span-1">
            <x-ui.logo variant="light" />
            <p class="mt-5 max-w-sm text-sm leading-relaxed">
                Local rides, rentals and outstation one-way and round trips from {{ config('site.region.city') }}
                ({{ config('site.region.city_alt') }}), run by the Helping Hand Drivers Association.
            </p>
            <a href="{{ route('booking.create') }}" class="btn btn-primary mt-6">
                Book a Ride <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        </div>

        <nav aria-labelledby="footer-pages">
            <h2 id="footer-pages" class="text-sm font-bold tracking-wide text-white uppercase">Pages</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach (config('site.navigation') as $item)
                    <li><a href="{{ route($item['route']) }}" @if (request()->routeIs($item['active'])) aria-current="page" @endif class="text-white hover:text-brand-cyan aria-[current=page]:font-semibold aria-[current=page]:text-white">{{ $item['label'] }}</a></li>
                @endforeach
                {{-- Published custom pages set to "Website footer" (Admin → Website Pages). --}}
                @foreach (\App\Models\Page::navigation('footer') as $item)
                    <li><a href="{{ $item['url'] }}" class="text-white hover:text-brand-cyan">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <nav aria-labelledby="footer-services">
            <h2 id="footer-services" class="text-sm font-bold tracking-wide text-white uppercase">Services</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach (app(\App\Content\ServiceCatalog::class)->all() as $service)
                    <li><a href="{{ route('booking.create', \App\Content\ServiceCatalog::bookingParams($service)) }}" class="text-white hover:text-brand-cyan">{{ $service['title'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div>
            <h2 class="text-sm font-bold tracking-wide text-white uppercase">Get in touch</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @if ($contact['phone'])
                    <li class="flex gap-2"><x-ui.icon name="phone" class="size-4 text-brand-green" /><a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}" class="text-white hover:text-brand-cyan">{{ $contact['phone'] }}</a></li>
                @endif
                @if ($whatsappUrl)
                    <li class="flex gap-2"><x-ui.icon name="whatsapp" class="size-4 text-brand-green" /><a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="text-white hover:text-brand-cyan">Chat on WhatsApp</a></li>
                @endif
                @if ($contact['email'])
                    <li class="flex gap-2"><x-ui.icon name="mail" class="size-4 text-brand-green" /><a href="mailto:{{ $contact['email'] }}" class="break-all text-white hover:text-brand-cyan">{{ $contact['email'] }}</a></li>
                @endif
                @if ($contact['address'])
                    <li class="flex gap-2"><x-ui.icon name="pin" class="size-4 text-brand-green" />{{ $contact['address'] }}</li>
                @endif
                @unless ($contact['phone'] || $whatsappUrl || $contact['email'])
                    <li><a href="{{ route('contact') }}" class="text-white hover:text-brand-cyan">Contact details coming soon</a></li>
                @endunless
            </ul>

            @if ($social)
                <ul class="mt-5 flex flex-wrap gap-3 text-sm">
                    @foreach ($social as $network => $url)
                        <li><a href="{{ $url }}" rel="noopener" target="_blank" class="capitalize text-white hover:text-brand-cyan">{{ $network }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="relative border-t border-white/15">
        <div class="container-site flex flex-col gap-2 py-6 text-xs sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ config('site.brand.full_name') }}. All rights reserved.</p>
            <p><a href="{{ route('photo-credits') }}" class="text-white underline-offset-2 hover:text-brand-cyan hover:underline">Photo credits</a> · {{ config('site.region.city') }}, India</p>
        </div>
    </div>
</footer>
