{{--
    Brand lockup: logo mark + wordmark. The mark lives at the path in
    config('site.brand.logo_mark'); replace that file with the official
    logo artwork to update it site-wide.

    Variants: dark (white backgrounds), light (footer). The homepage header
    recolours the dark variant over the hero via .header-logo in app.css.
--}}
@props(['variant' => 'dark'])

@php
    $light = $variant === 'light';
@endphp

<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'group inline-flex items-center gap-2.5 rounded-xl']) }}>
    <img src="{{ asset(config('site.brand.logo_mark')) }}" alt="" width="44" height="44" class="size-10 sm:size-11">
    <span class="flex flex-col leading-none">
        <span class="logo-name text-lg font-extrabold tracking-tight sm:text-xl {{ $light ? 'text-white' : 'text-brand-blue-800' }}">
            H2 <span class="{{ $light ? 'text-brand-green-200' : 'text-brand-green-600' }}">CABS</span>
        </span>
        <span class="logo-tagline mt-1 hidden text-[0.65rem] font-medium tracking-wide sm:block {{ $light ? 'text-white/70' : 'text-muted' }}">
            {{ config('site.brand.full_name') }}
        </span>
    </span>
    <span class="sr-only">— home</span>
</a>
