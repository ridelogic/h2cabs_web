{{--
    Reusable app download banner. Store buttons and QR codes appear only for
    real store URLs configured in .env (H2CABS_PASSENGER_ANDROID_URL /
    H2CABS_PASSENGER_IOS_URL). Without them the section says "coming soon" —
    no placeholder links, badges or QR codes pointing anywhere.
--}}
@php
    $stores = array_filter([
        'android' => config('site.apps.passenger.android'),
        'ios' => config('site.apps.passenger.ios'),
    ], fn ($url) => is_string($url) && str_starts_with($url, 'https://'));
    $storeNames = ['android' => 'Google Play', 'ios' => 'the App Store'];
@endphp

<section {{ $attributes->merge(['class' => 'section']) }} aria-labelledby="app-title">
    <div class="container-site">
        <div class="relative isolate overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand-blue-800 via-brand-blue-700 to-brand-green-700 px-6 py-12 text-white sm:px-12 lg:px-16 lg:py-16">
            <div aria-hidden="true" class="absolute -top-24 -left-24 -z-10 size-96 rounded-full bg-white/10 blur-2xl"></div>
            <div aria-hidden="true" class="absolute -right-20 -bottom-32 -z-10 size-[28rem] rounded-full bg-brand-green-500/30 blur-3xl"></div>

            <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-sm font-semibold ring-1 ring-white/25">
                        <x-ui.icon name="smartphone" class="size-4" />
                        {{ $stores ? 'H2 CABS app' : 'App coming soon' }}
                    </p>
                    <h2 id="app-title" class="heading-display mt-5 text-4xl sm:text-5xl">Your ride, just a tap away</h2>
                    <p class="mt-4 max-w-lg text-lg text-white/85">
                        @if ($stores)
                            Book local rides, rentals and outstation trips from your phone and keep your trip details in one place.
                        @else
                            The H2 CABS passenger app is on its way. Until it launches, book online or on WhatsApp in a minute.
                        @endif
                    </p>

                    @if ($stores)
                        <div class="mt-8 flex flex-wrap gap-6">
                            @foreach ($stores as $platform => $url)
                                <div class="flex items-center gap-4 rounded-2xl bg-white/10 p-3 pr-5 ring-1 ring-white/20">
                                    <span class="block size-24 shrink-0 overflow-hidden rounded-xl bg-white p-1.5" role="img" aria-label="QR code to download the H2 CABS app from {{ $storeNames[$platform] }}">
                                        {!! \App\Support\QrCode::svg($url, 160) !!}
                                    </span>
                                    <span>
                                        <span class="block text-xs text-white/70">Scan or tap</span>
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-light mt-2 !px-4 !py-2 text-sm">
                                            <x-ui.icon name="smartphone" class="size-4" /> Get it on {{ $storeNames[$platform] }}
                                        </a>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('booking.create') }}" class="btn btn-light px-7">Book online now</a>
                            @if ($wa = \App\Support\WhatsApp::chatUrl('Hello H2 CABS, I would like to book a ride.'))
                                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-whatsapp px-7"><x-ui.icon name="whatsapp" class="size-5" /> Book on WhatsApp</a>
                            @endif
                        </div>
                        <p class="mt-4 text-sm text-white/65">Download links and QR codes will appear here when the app is published.</p>
                    @endif
                </div>

                {{-- Phone mockup: an illustrative preview built in HTML, not a screenshot. --}}
                <div class="relative mx-auto w-full max-w-[17rem]" aria-hidden="true">
                    <div class="rotate-3 rounded-[2.6rem] bg-brand-blue-950 p-3 shadow-[0_40px_80px_-20px_rgb(0_0_0/0.6)] ring-1 ring-white/20 motion-safe:transition motion-safe:duration-500 motion-safe:hover:rotate-0">
                        <div class="relative overflow-hidden rounded-[2rem] bg-white text-ink">
                            <div class="mx-auto mt-2 h-5 w-24 rounded-full bg-brand-blue-950"></div>
                            <div class="relative mt-2 h-40 overflow-hidden bg-[linear-gradient(135deg,#dde9fb_25%,#effbf3_25%,#effbf3_50%,#dde9fb_50%,#dde9fb_75%,#effbf3_75%)] bg-[length:28px_28px]">
                                <svg viewBox="0 0 220 160" class="absolute inset-0 h-full w-full"><path d="M30 130 C 80 120, 90 60, 150 50 S 200 30, 205 20" fill="none" stroke="#0dbb70" stroke-width="5" stroke-linecap="round" stroke-dasharray="2 10"/><circle cx="30" cy="130" r="9" fill="#2bcb85" stroke="#fff" stroke-width="3"/><circle cx="205" cy="20" r="9" fill="#103b83" stroke="#fff" stroke-width="3"/></svg>
                            </div>
                            <div class="space-y-2.5 p-4">
                                <p class="text-sm font-bold text-brand-blue-950">Where to?</p>
                                <div class="flex items-center gap-2 rounded-xl bg-surface px-3 py-2 text-xs"><span class="size-2.5 rounded-full bg-brand-green-500"></span> White Town</div>
                                <div class="flex items-center gap-2 rounded-xl bg-surface px-3 py-2 text-xs"><span class="size-2.5 rounded-full bg-brand-blue-600"></span> Chennai</div>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div class="rounded-xl border-2 border-brand-green-600 bg-brand-green-50 p-2 text-center text-[11px] font-bold">Sedan</div>
                                    <div class="rounded-xl border border-line p-2 text-center text-[11px] font-bold">SUV</div>
                                </div>
                                <div class="rounded-xl bg-brand-green-600 py-2.5 text-center text-xs font-bold text-white">Book ride</div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-xs text-white/60">Illustrative preview</p>
                </div>
            </div>
        </div>
    </div>
</section>
