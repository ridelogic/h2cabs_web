{{--
    Closing call to action. Shows a WhatsApp button only when a valid
    business number is configured; the booking form is always offered.
--}}
@props(['title' => 'Ready to explore Pondicherry?', 'text' => 'Tell us where and when — we will take care of the drive.'])

@php
    $whatsappUrl = \App\Support\WhatsApp::chatUrl('Hello H2 CABS, I would like to book a ride.');
    $phone = config('site.contact.phone');
@endphp

<section class="section" aria-labelledby="cta-title">
    <div class="container-site">
        {{-- Same navy → blue gradient, cyan glow and arc as the footer (.brand-arc in app.css), over a faint photo. --}}
        <div class="brand-arc rounded-[2rem] bg-brand-navy-deep px-6 py-12 text-white sm:px-12 lg:px-16 lg:py-16">
            <x-ui.photo key="rock-beach" sizes="100vw" class="absolute inset-0 -z-20 size-full object-cover opacity-25" />
            <div aria-hidden="true" class="absolute inset-0 -z-20 bg-[radial-gradient(55%_90%_at_100%_0%,rgb(38_217_242/0.22),transparent_70%),linear-gradient(100deg,var(--color-brand-navy-deep)_0%,rgb(8_38_95/0.92)_45%,rgb(16_59_131/0.8)_100%)]"></div>

            <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <h2 id="cta-title" class="heading-display text-3xl sm:text-4xl">{{ $title }}</h2>
                    <p class="mt-3 text-lg text-white/85">{{ $text }}</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                    <a href="{{ route('booking.create') }}" class="btn btn-light px-7 text-base">
                        <x-ui.icon name="calendar" class="size-5" /> Book a Ride
                    </a>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-whatsapp px-7 text-base">
                            <x-ui.icon name="whatsapp" class="size-5" /> Chat on WhatsApp
                        </a>
                    @elseif ($phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="btn btn-ghost-light px-7 text-base">
                            <x-ui.icon name="phone" class="size-5" /> Call {{ $phone }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
