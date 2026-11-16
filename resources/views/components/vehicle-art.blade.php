{{--
    Original side-view illustration for a vehicle category (not a specific
    make or model). Used when no vehicle photo has been uploaded.
--}}
@props(['category' => 'sedan', 'label' => null])

@php
    $id = 'va-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
    // Body outline, window outline and wheel x-positions per category (viewBox 0 0 320 140).
    $shapes = [
        'mini' => ['M40 104V84c0-8 5-13 13-15l30-6 26-25c5-5 10-6 17-6h70c9 0 15 3 20 9l22 26 25 5c10 2 16 9 16 19v13c0 5-4 9-9 9H49c-5 0-9-4-9-9z', 'M116 64l20-20c3-3 6-4 10-4h28v24zM182 40h18c6 0 10 2 13 6l15 18h-46z', [92, 236]],
        'hatchback' => ['M40 104V84c0-8 5-13 13-15l30-6 26-25c5-5 10-6 17-6h70c9 0 15 3 20 9l22 26 25 5c10 2 16 9 16 19v13c0 5-4 9-9 9H49c-5 0-9-4-9-9z', 'M116 64l20-20c3-3 6-4 10-4h28v24zM182 40h18c6 0 10 2 13 6l15 18h-46z', [92, 236]],
        'sedan' => ['M20 104V86c0-8 5-13 13-15l46-9 30-24c6-5 12-7 20-7h66c9 0 16 3 22 9l26 22 42 7c11 2 17 9 17 19v12c0 5-4 9-9 9H29c-5 0-9-4-9-9z', 'M118 62l22-18c4-3 8-4 12-4h30v22zM190 40h20c6 0 11 2 15 6l18 16h-53z', [80, 244]],
        'premium' => ['M16 104V88c0-7 5-12 12-14l52-10 34-24c6-4 12-6 19-6h68c10 0 17 3 24 9l28 22 44 7c10 2 15 8 15 17v14c0 5-4 9-9 9H25c-5 0-9-4-9-9z', 'M122 64l24-17c4-3 8-4 12-4h30v21zM196 43h22c6 0 11 2 15 6l17 15h-54z', [78, 248]],
        'suv' => ['M18 106V72c0-9 6-15 15-16l40-4 28-26c5-4 10-6 17-6h96c9 0 15 3 20 9l24 23 34 5c10 1 16 9 16 18v32c0 5-4 9-9 9H27c-5 0-9-4-9-9z', 'M108 52l20-18c3-3 7-4 11-4h40v22zM188 30h24c6 0 10 2 14 6l16 16h-54z', [82, 246]],
        'large' => ['M14 106V46c0-9 7-16 16-16h208c10 0 18 4 24 11l34 36c3 3 6 5 10 6l2 1c5 2 8 6 8 11v11c0 5-4 9-9 9H23c-5 0-9-4-9-9z', 'M30 46h40v24H30zM80 46h40v24H80zM130 46h40v24h-40zM180 46h42v24h-42zM232 46h10c6 0 10 2 14 6l17 18h-41z', [70, 250]],
        'van' => ['M14 106V46c0-9 7-16 16-16h208c10 0 18 4 24 11l34 36c3 3 6 5 10 6l2 1c5 2 8 6 8 11v11c0 5-4 9-9 9H23c-5 0-9-4-9-9z', 'M30 46h40v24H30zM80 46h40v24H80zM130 46h40v24h-40zM180 46h42v24h-42zM232 46h10c6 0 10 2 14 6l17 18h-41z', [70, 250]],
    ];
    $shapes['muv'] = $shapes['suv'];
    [$body, $windows, $wheels] = $shapes[$category] ?? $shapes['sedan'];
@endphp

<svg {{ $attributes->merge(['class' => 'h-auto w-full']) }} viewBox="0 0 320 140" xmlns="http://www.w3.org/2000/svg"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif>
    <defs>
        <linearGradient id="{{ $id }}-body" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#e6edf6"/></linearGradient>
        <linearGradient id="{{ $id }}-glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#bcd6f7"/><stop offset="1" stop-color="#2b7de0"/></linearGradient>
    </defs>
    <ellipse cx="160" cy="124" rx="150" ry="8" fill="#061a3d" opacity=".25"/>
    <path d="{{ $body }}" fill="url(#{{ $id }}-body)" stroke="#0d3a7d" stroke-width="3" stroke-linejoin="round"/>
    <path d="{{ $windows }}" fill="url(#{{ $id }}-glass)" stroke="#0d3a7d" stroke-width="2" stroke-linejoin="round"/>
    {{-- H2 CABS stripe --}}
    <path d="M24 92h272" stroke="#1a9a4a" stroke-width="6" stroke-linecap="round"/>
    @foreach ($wheels as $x)
        <circle cx="{{ $x }}" cy="110" r="19" fill="#1b2230"/>
        <circle cx="{{ $x }}" cy="110" r="9" fill="#cfd8e3"/>
        <circle cx="{{ $x }}" cy="110" r="3" fill="#1b2230"/>
    @endforeach
</svg>
