@props(['seo' => null])

@php
    $seo ??= \App\Support\SeoMeta::make();
@endphp

<!DOCTYPE html>
<html lang="en-IN">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#08265f">

        <x-seo.meta :seo="$seo" />

        <link rel="icon" href="{{ asset(config('site.brand.logo_mark')) }}" type="image/svg+xml">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        {{-- Web fonts load without blocking first paint; text shows in the fallback font until they arrive. --}}
        @php($fontsUrl = 'https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|playfair-display:600,700,700i&display=swap')
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="preload" as="style" href="{{ $fontsUrl }}" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ $fontsUrl }}"></noscript>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <x-seo.organization-schema />

        @stack('head')
    </head>
    <body class="min-h-screen">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:font-semibold focus:text-brand-blue-800 focus:shadow-lg">
            Skip to main content
        </a>

        <x-site.topbar />
        <x-site.header />

        <main id="main" tabindex="-1">
            {{ $slot }}
        </main>

        <x-site.footer />
    </body>
</html>
