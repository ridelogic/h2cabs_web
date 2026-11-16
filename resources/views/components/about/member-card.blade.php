{{-- Public member profile. Shows initials when no approved photo has been uploaded. --}}
@props(['member', 'featured' => false])

<article @class([
    'flex h-full flex-col items-center rounded-(--radius-card) p-6 text-center',
    'bg-gradient-to-br from-brand-blue-800 to-brand-green-700 text-white shadow-lift' => $featured,
    'card' => ! $featured,
])>
    @if ($member->photoUrl())
        <img src="{{ $member->photoUrl() }}" alt="Photo of {{ $member->name }}" loading="lazy" decoding="async" width="112" height="112"
            @class(['rounded-full object-cover ring-4', 'size-28 ring-white/40' => $featured, 'size-24 ring-brand-green-100' => ! $featured])>
    @else
        <span aria-hidden="true" @class([
            'flex items-center justify-center rounded-full font-display font-bold ring-4',
            'size-28 bg-white/15 text-4xl text-white ring-white/30' => $featured,
            'size-24 bg-gradient-to-br from-brand-blue-600 to-brand-green-600 text-3xl text-white ring-brand-green-100' => ! $featured,
        ])>{{ $member->initials() }}</span>
    @endif

    @if ($member->role)
        <p @class(['mt-5 text-xs font-bold tracking-wider uppercase', 'text-brand-green-200' => $featured, 'text-brand-green-700' => ! $featured])>{{ $member->role }}</p>
    @endif
    <h3 @class(['mt-1 font-bold', 'text-2xl' => $featured, 'text-lg text-brand-blue-950' => ! $featured])>{{ $member->name }}</h3>

    @if ($member->member_since)
        <p @class(['mt-1 text-sm', 'text-white/75' => $featured, 'text-muted' => ! $featured])>Member since {{ $member->member_since }}</p>
    @endif
    @if ($member->bio)
        <p @class(['mt-3 text-sm', 'text-white/85' => $featured, 'text-muted' => ! $featured])>{{ $member->bio }}</p>
    @endif
    @if ($member->telHref())
        <a href="{{ $member->telHref() }}" @class(['mt-3 inline-flex items-center gap-1.5 text-sm font-semibold', 'text-white' => $featured, 'text-brand-blue-700' => ! $featured])>
            <x-ui.icon name="phone" class="size-4" /> {{ $member->phone }}
        </a>
    @endif
</article>
