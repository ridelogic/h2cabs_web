{{--
    Responsive photo from config/photos.php. Files are 3:2 WebP at
    public/images/photos/{key}-{width}.webp. Lazy-loaded unless `eager`
    (use eager only for the largest above-the-fold image).
--}}
@props(['key', 'alt' => null, 'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', 'eager' => false])

@php
    $photo = config('photos.'.$key);
    $widths = $photo['widths'];
    $srcset = collect($widths)->map(fn ($w) => asset("images/photos/{$key}-{$w}.webp")." {$w}w")->implode(', ');
@endphp

<img
    src="{{ asset("images/photos/{$key}-{$widths[0]}.webp") }}"
    srcset="{{ $srcset }}"
    sizes="{{ $sizes }}"
    alt="{{ $alt ?? $photo['alt'] }}"
    width="{{ $widths[0] }}"
    height="{{ intdiv($widths[0] * 2, 3) }}"
    @if ($eager) fetchpriority="high" loading="eager" @else loading="lazy" @endif
    decoding="async"
    {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}
>
