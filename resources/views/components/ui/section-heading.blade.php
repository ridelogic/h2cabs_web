@props(['id', 'eyebrow' => null, 'title', 'align' => 'center', 'tone' => 'dark'])

@php
    $centered = $align === 'center';
    $light = $tone === 'light';
@endphp

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $centered]) }}>
    @if ($eyebrow)
        <p @class(['eyebrow', 'justify-center' => $centered, '!text-brand-green-200' => $light])>{{ $eyebrow }}</p>
    @endif
    <h2 id="{{ $id }}" @class([
        'heading-display mt-3 text-3xl sm:text-4xl',
        'text-brand-blue-950' => ! $light,
        'text-white' => $light,
    ])>{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <p @class(['mt-4 text-lg text-pretty', 'text-muted' => ! $light, 'text-white/80' => $light])>{{ $slot }}</p>
    @endif
</div>
