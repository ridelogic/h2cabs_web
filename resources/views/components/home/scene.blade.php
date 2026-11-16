{{--
    Lightweight vector illustrations of Pondicherry landmarks, used until
    licensed photography is available. Set `image` on a destination in
    App\Content\HomePageContent to use a real photo instead.
--}}
@props(['name', 'label'])

{{-- Unique per instance so gradient ids never collide when a scene is reused on a page. --}}
@php($id = 'scene-'.$name.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)))

<svg {{ $attributes->merge(['class' => 'block h-full w-full']) }} viewBox="0 0 400 260" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $label }}">
    @switch($name)
        @case('promenade')
            <defs>
                <linearGradient id="{{ $id }}-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd9a8"/><stop offset="1" stop-color="#cfe6f7"/></linearGradient>
                <linearGradient id="{{ $id }}-sea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2b7de0"/><stop offset="1" stop-color="#0f4c9c"/></linearGradient>
            </defs>
            <rect width="400" height="260" fill="url(#{{ $id }}-sky)"/>
            <circle cx="300" cy="120" r="34" fill="#ffb648" opacity=".9"/>
            <rect y="140" width="400" height="70" fill="url(#{{ $id }}-sea)"/>
            <path d="M0 158h400M30 176h120M220 186h150" stroke="#fff" stroke-opacity=".35" stroke-width="3" stroke-linecap="round"/>
            <path d="M118 140 126 50h14l8 90z" fill="#f4efe6"/>
            <path d="M120 62h26M121 88h24M123 114h20" stroke="#c9bfae" stroke-width="3"/>
            <rect x="122" y="38" width="22" height="14" rx="2" fill="#0d3a7d"/>
            <path d="m118 38 15-12 15 12z" fill="#13803c"/>
            <rect y="200" width="400" height="60" fill="#e9dcc5"/>
            <rect y="196" width="400" height="8" fill="#cbb994"/>
            <path d="M42 200c2-30 0-52-6-72" stroke="#6b4f2a" stroke-width="5" fill="none" stroke-linecap="round"/>
            <path d="M36 128c-18-6-32 0-38 10 14-4 26-4 38-10zm0 0c14-14 30-14 40-8-14 0-26 4-40 8zm0 0c-6-16-2-28 8-34-4 12-6 22-8 34zm0 0c18 0 30 8 34 18-12-8-22-12-34-18z" fill="#1a9a4a"/>
            @break

        @case('white-town')
            <rect width="400" height="260" fill="#cfe6f7"/>
            <rect x="40" y="60" width="320" height="160" fill="#f4c54f"/>
            <rect x="40" y="52" width="320" height="14" fill="#fff"/>
            @foreach ([70, 150, 230, 310] as $x)
                <path d="M{{ $x }} 200v-62a20 20 0 0 1 40 0v62z" fill="#fff"/>
                <path d="M{{ $x + 6 }} 196v-56a14 14 0 0 1 28 0v56z" fill="#1663c7" opacity=".85"/>
                <path d="M{{ $x + 20 }} 126v70" stroke="#fff" stroke-width="2"/>
            @endforeach
            <rect x="40" y="96" width="320" height="6" fill="#fff" opacity=".7"/>
            <rect y="220" width="400" height="40" fill="#d8d2c6"/>
            <circle cx="40" cy="70" r="30" fill="#e0457b"/>
            <circle cx="66" cy="60" r="20" fill="#f06a98"/>
            <circle cx="22" cy="96" r="22" fill="#e0457b"/>
            <circle cx="30" cy="62" r="14" fill="#1a9a4a"/>
            <circle cx="372" cy="210" r="22" fill="#26b35a"/>
            <circle cx="386" cy="190" r="16" fill="#1a9a4a"/>
            @break

        @case('auroville')
            <rect width="400" height="260" fill="#dff1e6"/>
            <circle cx="320" cy="60" r="22" fill="#fff6d6"/>
            <path d="M0 190c60-30 120-30 200-10s150 10 200-10v90H0z" fill="#b4ebc8"/>
            <ellipse cx="200" cy="198" rx="120" ry="14" fill="#13803c" opacity=".25"/>
            <circle cx="200" cy="140" r="62" fill="#d9a520"/>
            <g fill="#f3c94a">
                @foreach (range(0, 5) as $row)
                    <ellipse cx="200" cy="{{ 92 + $row * 18 }}" rx="{{ [30, 50, 60, 60, 52, 34][$row] }}" ry="6"/>
                @endforeach
            </g>
            <circle cx="182" cy="118" r="16" fill="#fff" opacity=".25"/>
            @foreach ([[40, 170, 34], [86, 180, 26], [330, 172, 32], [370, 184, 24]] as [$x, $y, $r])
                <rect x="{{ $x - 3 }}" y="{{ $y }}" width="6" height="30" fill="#6b4f2a"/>
                <circle cx="{{ $x }}" cy="{{ $y - $r / 2 }}" r="{{ $r }}" fill="#1a9a4a"/>
            @endforeach
            <rect y="226" width="400" height="34" fill="#c58d52"/>
            @break

        @case('paradise')
            <rect width="400" height="260" fill="#cfe6f7"/>
            <rect y="120" width="400" height="140" fill="#2b9bd6"/>
            <path d="M0 150h400M0 190h400M0 230h400" stroke="#fff" stroke-opacity=".25" stroke-width="3" stroke-dasharray="40 30"/>
            <path d="M170 128c40-22 160-26 230-6v20H160z" fill="#f2deb0"/>
            @foreach ([[250, 1], [300, -1], [350, 1]] as [$x, $dir])
                <path d="M{{ $x }} 124c{{ 4 * $dir }}-24 {{ 2 * $dir }}-44 {{ -6 * $dir }}-60" stroke="#6b4f2a" stroke-width="5" fill="none" stroke-linecap="round"/>
                <path d="M{{ $x - 6 * $dir }} 64c-20-4-34 4-38 14 14-6 26-6 38-14zm0 0c16-12 32-10 40-2-14-2-26 0-40 2zm0 0c18 4 26 14 28 24-10-10-18-16-28-24z" fill="#1a9a4a"/>
            @endforeach
            <path d="M60 200h110l-16 22H76z" fill="#fff"/>
            <path d="M74 200v-26h50v26" fill="#e74c3c"/>
            <rect x="80" y="166" width="38" height="8" fill="#0d3a7d"/>
            @break

        @case('basilica')
            <rect width="400" height="260" fill="#dde9fb"/>
            <rect x="110" y="90" width="180" height="140" fill="#f6efe3"/>
            <path d="M110 90V50l20-26 20 26v40zM250 90V50l20-26 20 26v40z" fill="#efe3cf"/>
            <path d="M130 24V8M270 24V8M124 13h12M264 13h12" stroke="#0d3a7d" stroke-width="3"/>
            <path d="m150 90 50-44 50 44z" fill="#efe3cf"/>
            <circle cx="200" cy="120" r="22" fill="#1663c7"/>
            <circle cx="200" cy="120" r="22" fill="none" stroke="#f4c54f" stroke-width="4" stroke-dasharray="6 5"/>
            <path d="M180 230v-50a20 20 0 0 1 40 0v50z" fill="#8a5a2b"/>
            <path d="M130 200v-40a10 10 0 0 1 20 0v40zM250 200v-40a10 10 0 0 1 20 0v40zM124 70v-14a6 6 0 0 1 12 0v14zM264 70v-14a6 6 0 0 1 12 0v14z" fill="#1663c7" opacity=".8"/>
            <rect y="230" width="400" height="30" fill="#c9d6e6"/>
            <circle cx="50" cy="212" r="26" fill="#1a9a4a"/>
            <circle cx="355" cy="214" r="24" fill="#26b35a"/>
            @break

        @case('ashram')
            <rect width="400" height="260" fill="#effbf3"/>
            <rect x="0" y="90" width="400" height="140" fill="#fff"/>
            <rect x="0" y="84" width="400" height="10" fill="#d4dde8"/>
            @foreach ([40, 120, 280, 340] as $x)
                <rect x="{{ $x }}" y="120" width="30" height="50" rx="2" fill="#0f4c9c" opacity=".2"/>
            @endforeach
            <path d="M180 230v-80h40v80z" fill="#13803c" opacity=".85"/>
            <rect x="196" y="40" width="8" height="140" fill="#6b4f2a" transform="rotate(4 200 180)"/>
            <circle cx="170" cy="60" r="44" fill="#1a9a4a"/>
            <circle cx="236" cy="54" r="40" fill="#26b35a"/>
            <circle cx="206" cy="30" r="34" fill="#13803c"/>
            @foreach ([[160, 40], [190, 70], [230, 38], [250, 70], [208, 18], [150, 74]] as [$x, $y])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#fbe36a"/>
            @endforeach
            <rect y="230" width="400" height="30" fill="#e9dcc5"/>
            @break
    @endswitch
</svg>
