@php
    // Hero slides: one per trip type, Rental first. Buttons go to the booking
    // form preset to that trip type, and to the matching Services section.
    $slides = [
        [
            'title' => ['Taxi Rentals', 'Made Easy'],
            'text' => 'Book a comfortable cab for hourly, half-day or full-day travel.',
            'primary' => ['label' => 'Book Rental', 'url' => route('booking.create', ['type' => 'rental'])],
            'secondary' => ['label' => 'Explore Rentals', 'url' => route('services').'#types'],
        ],
        [
            'title' => ['Local Taxi', 'Across Puducherry'],
            'text' => 'Point-to-point rides around town with drivers from the Helping Hand Drivers Association.',
            'primary' => ['label' => 'Book Local Taxi', 'url' => route('booking.create', ['type' => 'local'])],
            'secondary' => ['label' => 'Our Services', 'url' => route('services')],
        ],
        [
            'title' => ['Outstation Trips', 'Made Simple'],
            'text' => 'One-way drops and round trips from Puducherry to other cities.',
            'primary' => ['label' => 'Book Outstation', 'url' => route('booking.create', ['type' => 'outstation_one_way'])],
            'secondary' => ['label' => 'Explore Outstation', 'url' => route('services').'#outstation'],
        ],
    ];
    $headingClass = 'mt-7 lg:short:mt-4 text-[2.9rem] leading-[0.98] font-extrabold tracking-[-0.03em] sm:text-6xl lg:text-[clamp(3.5rem,8.5svh,4.5rem)] xl:text-[clamp(4rem,9.5svh,5.75rem)] 2xl:text-[clamp(4rem,9.5svh,6rem)]';
    $arrowClass = 'absolute z-10 flex size-11 items-center justify-center rounded-full border border-white/70 bg-[#123a8a]/55 text-white backdrop-blur transition hover:border-white hover:bg-[#1d4fb8]/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-cyan lg:top-1/2 lg:size-14 lg:-translate-y-1/2';
@endphp

{{--
    Homepage hero: navy → ocean-blue gradient with the night-time Puducherry
    waterfront and silver sedan (config('site.home.hero_car')) bottom-right.
    Pulled up under the transparent homepage header (-mt-18/-mt-20).
    Behaviour: resources/js/hero-slider.js (without JS the first slide shows
    and the arrows stay hidden).
--}}
<section aria-roledescription="carousel" aria-label="H2 CABS services" data-hero-slider
    class="relative isolate -mt-18 overflow-hidden bg-[#08205a] text-white sm:-mt-20 lg:-mt-24">
    {{-- Gradient and curved light shapes --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-20 bg-[linear-gradient(100deg,#061a4a_0%,#0a2766_34%,#0d3f97_62%,#1667c4_84%,#1e86d8_100%)]"></div>
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-20">
        <div class="absolute -top-[40%] right-[-25%] size-[70rem] rounded-full bg-[radial-gradient(closest-side,rgb(56_189_248/0.35),transparent)]"></div>
        <div class="absolute top-[18%] left-[38%] h-[90rem] w-[120rem] rounded-[100%] border-t border-white/10 bg-white/[0.03]"></div>
        <div class="absolute bottom-[-30%] left-[-20%] size-[46rem] rounded-full bg-[#04123a]/50 blur-3xl"></div>
    </div>

    <div class="relative px-4 pt-32 sm:px-8 sm:pt-40 lg:flex lg:min-h-[min(46rem,calc(100svh-3.5rem))] lg:items-center lg:pt-[max(8rem,15svh)] lg:short:pt-28 lg:pr-0 lg:pb-[max(3rem,8svh)] lg:pl-[max(7.3vw,6rem)] xl:min-h-[min(50rem,calc(100svh-3.5rem))]">
        {{-- Slides stack in one grid cell; only the active one is visible. --}}
        <div id="hero-slides" class="relative z-10 grid lg:max-w-[50%]">
            @foreach ($slides as $i => $slide)
                <div data-hero-slide data-active="{{ $i === 0 ? 'true' : 'false' }}" @if ($i > 0) aria-hidden="true" inert @endif
                    role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}"
                    class="[grid-area:1/1] transition-[opacity,translate,visibility] duration-500 ease-out data-[active=false]:invisible data-[active=false]:translate-y-3 data-[active=false]:opacity-0">
                    <p class="text-sm font-extrabold tracking-[0.2em] text-brand-cyan uppercase xl:text-base">H2 CABS</p>
                    <p class="mt-4 lg:short:mt-3 inline-flex items-center gap-2.5 rounded-full border border-white/35 bg-[#0b2768]/50 px-5 py-2.5 text-sm font-bold text-white backdrop-blur xl:px-6 xl:py-3 xl:text-base">
                        <svg class="size-4 shrink-0 xl:size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 4 5v6.1c0 5 3.4 9.7 8 10.9 4.6-1.2 8-5.9 8-10.9V5l-8-3zm0 3.2 5 1.9v4c0 3.5-2.1 6.8-5 7.8V5.2z"/></svg>
                        RELIABLE · SAFE · ON TIME
                    </p>
                    @php($second = '<span class="bg-[linear-gradient(90deg,var(--color-brand-cyan),#7eeafa)] bg-clip-text pr-2 italic text-transparent">'.e($slide['title'][1]).'</span>')
                    @if ($i === 0)
                        <h1 id="hero-title" class="{{ $headingClass }}">{{ $slide['title'][0] }}<br>{!! $second !!}</h1>
                    @else
                        <h2 class="{{ $headingClass }}">{{ $slide['title'][0] }}<br>{!! $second !!}</h2>
                    @endif
                    <p class="mt-6 lg:short:mt-4 max-w-2xl text-lg leading-snug text-pretty text-white sm:text-xl lg:text-2xl xl:max-w-none xl:text-[1.6rem]">{{ $slide['text'] }}</p>
                    <div class="mt-9 lg:short:mt-6 flex flex-col gap-4 sm:flex-row sm:gap-6">
                        <a href="{{ $slide['primary']['url'] }}"
                            class="inline-flex min-h-14 items-center justify-center gap-3 whitespace-nowrap rounded-full bg-[linear-gradient(135deg,#25d488,var(--color-brand-green)_50%,var(--color-brand-green-hover))] px-10 text-lg font-bold text-white lg:px-7 shadow-[0_14px_34px_-10px_rgb(13_187_112/0.75)] transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-cyan xl:min-h-[4.5rem] lg:short:min-h-14 xl:px-11 xl:text-xl">
                            {{ $slide['primary']['label'] }} <x-ui.icon name="arrow-right" class="size-5 xl:size-6" />
                        </a>
                        <a href="{{ $slide['secondary']['url'] }}"
                            class="inline-flex min-h-14 items-center justify-center gap-3 whitespace-nowrap rounded-2xl border-2 border-white bg-[#08205a]/40 px-10 text-lg font-bold text-white lg:px-7 transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-cyan xl:min-h-[4.5rem] lg:short:min-h-14 xl:px-11 xl:text-xl">
                            {{ $slide['secondary']['label'] }} <x-ui.icon name="arrow-right" class="size-5 xl:size-6" />
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{--
            Waterfront scene with the car. Below the text on small screens;
            from lg up it fills the right half, bottom-aligned, with its left
            and top edges faded into the gradient.
        --}}
        <div class="relative -mx-4 mt-8 sm:-mx-8 lg:absolute lg:right-0 lg:bottom-0 lg:mx-0 lg:mt-0 lg:w-[44%] xl:w-[50%]">
            <img src="{{ asset(config('site.home.hero_car')) }}" alt="Silver sedan on the Puducherry waterfront at night, with the lighthouse and palm trees"
                class="hero-scene block h-auto w-full" width="784" height="717" fetchpriority="high">
        </div>
    </div>

    {{-- Slider controls (revealed by hero-slider.js when there is more than one slide) --}}
    <button type="button" data-hero-prev hidden aria-controls="hero-slides" aria-label="Previous slide" class="{{ $arrowClass }} bottom-[45vw] left-2 sm:left-4 lg:bottom-auto lg:left-6">
        <x-ui.icon name="chevron-left" class="size-6" />
    </button>
    <button type="button" data-hero-next hidden aria-controls="hero-slides" aria-label="Next slide" class="{{ $arrowClass }} right-2 bottom-[45vw] sm:right-4 lg:right-6 lg:bottom-auto">
        <x-ui.icon name="chevron-right" class="size-6" />
    </button>
    <p class="sr-only" aria-live="polite" data-hero-status></p>
</section>
