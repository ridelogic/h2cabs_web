{{-- Hero illustration: Pondicherry seafront with the old lighthouse and an H2 CABS taxi. --}}
<svg {{ $attributes->merge(['class' => 'block h-auto w-full']) }} viewBox="0 0 600 480" xmlns="http://www.w3.org/2000/svg" role="img"
    aria-label="Illustration of an H2 CABS taxi driving along the Pondicherry seafront promenade, with the old lighthouse, palm trees and the Bay of Bengal at sunrise">
    <defs>
        <linearGradient id="hero-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#bfe0f7"/><stop offset=".7" stop-color="#ffe2b8"/></linearGradient>
        <linearGradient id="hero-sea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2b7de0"/><stop offset="1" stop-color="#0d3a7d"/></linearGradient>
        <linearGradient id="hero-car" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0f4c9c"/><stop offset="1" stop-color="#1663c7"/></linearGradient>
        <clipPath id="hero-clip"><rect width="600" height="480" rx="32"/></clipPath>
    </defs>
    <g clip-path="url(#hero-clip)">
        <rect width="600" height="480" fill="url(#hero-sky)"/>
        <circle cx="430" cy="200" r="58" fill="#ffb648" opacity=".85"/>
        <circle cx="430" cy="200" r="86" fill="#ffb648" opacity=".18"/>
        <path d="M60 90c14-8 30-8 44 0M120 70c10-6 22-6 32 0" stroke="#0d3a7d" stroke-opacity=".35" stroke-width="3" fill="none" stroke-linecap="round"/>

        {{-- Sea --}}
        <rect y="228" width="600" height="110" fill="url(#hero-sea)"/>
        <path d="M20 252h140M260 262h200M80 290h180M380 300h180M30 318h110" stroke="#fff" stroke-opacity=".3" stroke-width="3" stroke-linecap="round"/>
        <path d="M330 236h200" stroke="#ffd9a0" stroke-opacity=".6" stroke-width="4" stroke-linecap="round"/>

        {{-- Old lighthouse --}}
        <path d="M168 228 180 86h22l12 142z" fill="#f6f1e7"/>
        <path d="M172 120h38M170 156h42M168 192h46" stroke="#d6cbb7" stroke-width="4"/>
        <rect x="176" y="66" width="30" height="22" rx="3" fill="#0d3a7d"/>
        <rect x="182" y="71" width="18" height="12" rx="2" fill="#ffd36b"/>
        <path d="m170 66 21-18 21 18z" fill="#13803c"/>

        {{-- Promenade wall and road --}}
        <rect y="330" width="600" height="18" fill="#d8c7a4"/>
        <rect y="346" width="600" height="134" fill="#3b4656"/>
        <path d="M0 412h600" stroke="#fff" stroke-opacity=".7" stroke-width="5" stroke-dasharray="36 28"/>
        <rect y="346" width="600" height="8" fill="#2b3442"/>

        {{-- Palms --}}
        <path d="M70 336c4-50 0-90-12-128" stroke="#6b4f2a" stroke-width="9" fill="none" stroke-linecap="round"/>
        <path d="M58 208c-30-10-54 0-64 18 24-8 44-8 64-18zm0 0c22-24 50-24 66-14-24 0-44 6-66 14zm0 0c-10-28-4-48 14-58-8 20-10 38-14 58zm0 0c30 0 50 14 56 30-20-14-36-20-56-30zm0 0c-20 10-30 30-28 46 6-18 14-32 28-46z" fill="#1a9a4a"/>
        <path d="M548 336c-4-40 2-74 16-104" stroke="#6b4f2a" stroke-width="8" fill="none" stroke-linecap="round"/>
        <path d="M564 232c26-10 48-2 58 14-22-6-40-6-58-14zm0 0c-20-20-44-20-58-10 20 0 38 4 58 10zm0 0c8-24 2-42-12-50 6 16 8 32 12 50zm0 0c-26 2-44 14-50 28 18-12 32-18 50-28z" fill="#26b35a"/>

        {{-- Taxi --}}
        <g transform="translate(196 340)">
            <ellipse cx="110" cy="92" rx="120" ry="10" fill="#000" opacity=".25"/>
            <path d="M8 76V54c0-8 4-14 12-16l36-8 30-26c6-5 12-6 20-6h70c8 0 14 2 20 8l26 26 26 6c10 2 16 10 16 20v18c0 6-4 10-10 10H18c-6 0-10-4-10-10z" fill="url(#hero-car)"/>
            <path d="M74 30 100 8c4-3 8-4 12-4h30v26zM150 4h24c6 0 10 2 14 6l22 20h-60z" fill="#cfe6f7"/>
            <rect x="8" y="50" width="248" height="10" fill="#1a9a4a"/>
            <rect x="122" y="-14" width="44" height="14" rx="4" fill="#26b35a"/>
            <text x="144" y="-4" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="10" font-weight="800" fill="#fff">H2 CABS</text>
            <rect x="244" y="40" width="12" height="8" rx="2" fill="#ffd36b"/>
            <rect x="8" y="40" width="10" height="8" rx="2" fill="#e74c3c"/>
            <circle cx="64" cy="86" r="20" fill="#1b2230"/><circle cx="64" cy="86" r="9" fill="#9aa8bb"/>
            <circle cx="206" cy="86" r="20" fill="#1b2230"/><circle cx="206" cy="86" r="9" fill="#9aa8bb"/>
        </g>
    </g>
</svg>
