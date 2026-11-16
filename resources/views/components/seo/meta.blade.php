@props(['seo'])

<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
<meta name="robots" content="{{ $seo->robots() }}">
@if ($seo->canonical)
    <link rel="canonical" href="{{ $seo->canonical }}">
@endif

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ config('site.brand.name') }}">
<meta property="og:locale" content="{{ config('site.seo.locale') }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
@if ($seo->canonical)
    <meta property="og:url" content="{{ $seo->canonical }}">
@endif
<meta property="og:image" content="{{ $seo->image }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ config('site.brand.name') }} — {{ config('site.brand.full_name') }}">

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
<meta name="twitter:image" content="{{ $seo->image }}">
