{{--
    Dark-blue contact bar above the header. Phone, email and social links
    come from config('site.contact' / 'site.social') — .env or Admin →
    Settings — so nothing here is hard-coded; empty values are hidden.
--}}
@php
    $phone = config('site.contact.phone');
    $email = config('site.contact.email');
    $social = array_filter(config('site.social'));
    $labels = ['twitter' => 'X (Twitter)'];
@endphp

<div class="border-b border-white/15 bg-brand-navy text-sm text-white xl:text-base">
    <div class="mx-auto flex min-h-11 w-full items-center justify-between gap-4 px-4 py-2 xl:min-h-14 sm:px-6 lg:px-[5vw]">
        <ul class="flex min-w-0 flex-wrap items-center gap-y-1">
            @if ($phone)
                <li class="pr-4 sm:pr-5">
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="inline-flex items-center gap-2 font-medium hover:text-brand-cyan">
                        <x-ui.icon name="phone-solid" class="size-5 text-brand-green" /> {{ $phone }}
                    </a>
                </li>
            @endif
            @if ($email)
                <li class="hidden border-l border-white/15 px-5 sm:block">
                    <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 hover:text-brand-cyan">
                        <x-ui.icon name="mail-solid" class="size-5 text-brand-green" /> {{ $email }}
                    </a>
                </li>
            @endif
            <li class="hidden items-center gap-2 border-l border-white/15 px-5 md:inline-flex">
                <x-ui.icon name="pin-solid" class="size-5 text-brand-green" /> {{ config('site.region.city') }}
            </li>
        </ul>

        @if ($social)
            <ul class="flex shrink-0 items-center gap-1" aria-label="Social media">
                @foreach ($social as $network => $url)
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="flex size-9 items-center justify-center rounded-full text-white transition hover:bg-white/10 hover:text-brand-cyan">
                            <x-ui.icon :name="$network" class="size-5" /><span class="sr-only">H2 CABS on {{ $labels[$network] ?? ucfirst($network) }} (opens in a new tab)</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
