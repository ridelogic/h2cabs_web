@php
    use Illuminate\Support\Str;

    $tz = config('site.booking.timezone');
    $now = now($tz);
    // First name for the greeting; "Admin" when the account name is blank or an email address.
    $firstName = Str::of((string) auth()->user()?->name)->trim()->explode(' ')->first();
    $firstName = blank($firstName) || str_contains($firstName, '@') ? 'Admin' : $firstName;
    $greeting = match (true) {
        $now->hour < 12 => 'Good morning',
        $now->hour < 17 => 'Good afternoon',
        default => 'Good evening',
    };
    $configured = collect($checks)->where('ok', true)->count();

    $kpis = [
        ['label' => 'Total Members', 'value' => $stats['members'], 'hint' => $stats['publicMembers'].' shown on website', 'route' => 'admin.members.index', 'icon' => 'users', 'tile' => 'bg-admin-accent text-brand-blue-700'],
        ['label' => 'Active Vehicles', 'value' => $stats['vehicles'], 'hint' => $stats['bookableVehicles'].' bookable online', 'route' => 'admin.vehicles.index', 'icon' => 'car', 'tile' => 'bg-emerald-50 text-admin-green'],
        ['label' => 'Active Fares', 'value' => $stats['fares'], 'hint' => $stats['proposedFares'].' still proposed', 'route' => 'admin.fares.index', 'icon' => 'receipt', 'tile' => 'bg-amber-50 text-amber-600'],
        ['label' => 'Booking Requests', 'value' => $stats['bookings'], 'hint' => $stats['newBookings'] ? $stats['newBookings'].' new to review' : 'No new requests', 'route' => 'admin.bookings.index', 'icon' => 'calendar', 'tile' => 'bg-violet-50 text-violet-600'],
    ];

    $actions = [
        ['label' => 'Add Vehicle', 'hint' => 'Create a new vehicle', 'route' => 'admin.vehicles.create', 'icon' => 'plus', 'tile' => 'bg-admin-green text-white'],
        ['label' => 'Configure Fares', 'hint' => 'Set and approve pricing', 'route' => 'admin.fares.index', 'icon' => 'receipt', 'tile' => 'bg-brand-blue-600 text-white'],
        ['label' => 'View Bookings', 'hint' => 'Manage booking requests', 'route' => 'admin.bookings.index', 'icon' => 'calendar', 'tile' => 'bg-violet-600 text-white'],
        ['label' => 'Manage Members', 'hint' => 'Association members', 'route' => 'admin.members.index', 'icon' => 'users', 'tile' => 'bg-amber-500 text-white'],
        ['label' => 'Website Settings', 'hint' => 'Contact details and logo', 'route' => 'admin.settings.edit', 'icon' => 'settings', 'tile' => 'bg-cyan-500 text-white'],
    ];
@endphp

<x-layouts.admin title="Dashboard" :header="false">
    {{-- Welcome --}}
    {{-- Real photo of the Pondicherry seafront (credited in config/photos.php). Below the text on phones, blended in behind it from md up. --}}
    <section class="relative isolate flex flex-col overflow-hidden rounded-3xl border border-admin-line bg-[#f5f9ff] shadow-admin md:min-h-[19rem] lg:min-h-[22rem]" aria-labelledby="welcome-title">
        <picture class="relative order-last block md:absolute md:inset-y-0 md:right-0 md:-z-10 md:w-[66%] xl:w-[62%]">
            <source media="(min-width: 1280px)" srcset="{{ asset('images/photos/promenade-hero-2000.webp') }}" type="image/webp">
            <img src="{{ asset('images/photos/promenade-hero-1280.webp') }}" alt="{{ config('photos.promenade-hero.alt') }}"
                class="aspect-[16/9] w-full object-cover object-[60%_55%] md:aspect-auto md:h-full md:[mask-image:linear-gradient(to_right,transparent,black_42%)]"
                width="1280" height="853" fetchpriority="high">
        </picture>
        <div class="absolute inset-0 -z-20 bg-[radial-gradient(70%_100%_at_0%_0%,#e6f0ff,transparent_70%)]" aria-hidden="true"></div>

        <div class="flex flex-col justify-center gap-5 p-6 sm:p-8 md:min-h-[inherit] md:max-w-[50%] md:pr-0 lg:gap-6 lg:p-12 xl:max-w-[46%]">
            <p class="inline-flex w-fit items-center gap-2 rounded-full border border-admin-line bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm">
                <x-ui.icon name="calendar" class="size-4 text-admin-green" />
                <time datetime="{{ $now->toDateString() }}">{{ $now->format('l, j F Y') }}</time>
            </p>
            <div>
                <h1 id="welcome-title" class="text-[1.75rem] leading-tight font-extrabold tracking-tight text-admin-navy sm:text-4xl lg:text-[2.75rem]">
                    {{ $greeting }}, <span class="text-admin-green">{{ $firstName }}!</span>
                </h1>
                <p class="mt-3 text-lg font-semibold text-admin-navy sm:text-xl">Welcome to H2 CABS Admin Portal</p>
                <p class="mt-1 text-base text-slate-600">Manage your fleet, bookings and settings from one place.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary"><x-ui.icon name="plus" class="size-4" /> Add Vehicle</a>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline !border-brand-blue-500 bg-white/90 !text-brand-blue-700 backdrop-blur hover:!bg-brand-blue-50">
                    <x-ui.icon name="calendar" class="size-4" /> View Bookings
                    @if ($stats['newBookings'])<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ $stats['newBookings'] }} new</span>@endif
                </a>
            </div>
        </div>
    </section>

    {{-- KPIs --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Summary">
        @foreach ($kpis as $kpi)
            <a href="{{ route($kpi['route']) }}" class="admin-kpi group">
                <span class="admin-tile {{ $kpi['tile'] }}"><x-ui.icon :name="$kpi['icon']" class="size-6" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-slate-500">{{ $kpi['label'] }}</span>
                    <span class="mt-0.5 block text-3xl leading-tight font-extrabold tracking-tight text-admin-navy">{{ number_format($kpi['value']) }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $kpi['hint'] }}</span>
                </span>
                <x-ui.icon name="chevron-right" class="absolute top-4 right-4 size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-admin-navy" />
            </a>
        @endforeach
    </section>

    @if ($stats['proposedFares'] > 0)
        <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex gap-3">
                <x-ui.icon name="info" class="size-5 shrink-0" />
                <span>{{ $stats['proposedFares'] }} active {{ Str::plural('fare', $stats['proposedFares']) }} {{ $stats['proposedFares'] === 1 ? 'is' : 'are' }} still <strong>proposed</strong>. Customers cannot book those vehicles for that trip type until you approve the fare.</span>
            </p>
            <a href="{{ route('admin.fares.index') }}" class="btn btn-outline shrink-0 bg-white">Review fares</a>
        </div>
    @endif

    {{-- Quick actions --}}
    <section class="admin-panel mt-6" aria-labelledby="actions-title">
        <div class="admin-panel-header">
            <div>
                <h2 id="actions-title" class="admin-panel-title"><x-ui.icon name="zap" class="size-5 text-admin-green" /> Quick Actions</h2>
                <p class="admin-panel-subtitle">Common tasks for running H2 CABS.</p>
            </div>
        </div>
        <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-5">
            @foreach ($actions as $action)
                <a href="{{ route($action['route']) }}" class="admin-action group xl:flex-col xl:items-start xl:gap-4 xl:p-5">
                    <span class="admin-tile size-12 shadow-md xl:size-14 {{ $action['tile'] }}"><x-ui.icon :name="$action['icon']" class="size-6 xl:size-7" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-bold text-admin-navy">{{ $action['label'] }}</span>
                        <span class="block text-sm text-slate-500">{{ $action['hint'] }}</span>
                    </span>
                    <x-ui.icon name="arrow-right" class="size-4 text-slate-300 xl:hidden transition group-hover:translate-x-0.5 group-hover:text-admin-navy" />
                </a>
            @endforeach
        </div>
    </section>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-2 [&>*]:min-w-0">
        {{-- Setup checklist --}}
        <section class="admin-panel" aria-labelledby="setup-title">
            <div class="admin-panel-header">
                <div>
                    <h2 id="setup-title" class="admin-panel-title"><x-ui.icon name="shield" class="size-5 text-admin-green" /> Setup Checklist</h2>
                    <p class="admin-panel-subtitle">Complete these to enable every booking feature.</p>
                </div>
                <div class="w-36 text-right">
                    <p class="text-sm font-semibold text-admin-navy">{{ $configured }} of {{ count($checks) }} configured</p>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Setup progress" aria-valuemin="0" aria-valuemax="{{ count($checks) }}" aria-valuenow="{{ $configured }}">
                        <div class="h-full rounded-full bg-admin-green" style="width: {{ round($configured / max(count($checks), 1) * 100) }}%"></div>
                    </div>
                </div>
            </div>
            <ul class="divide-y divide-admin-line">
                @foreach ($checks as $check)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 sm:flex-nowrap sm:px-6">
                        <span @class(['admin-tile size-10', 'bg-emerald-50 text-admin-green' => $check['ok'], 'bg-amber-50 text-amber-600' => ! $check['ok']])>
                            <x-ui.icon :name="$check['icon']" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1 basis-[calc(100%-3.5rem)] sm:basis-auto">
                            <p class="text-sm font-semibold text-admin-navy">{{ $check['title'] }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs"><code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[0.6875rem] text-slate-600">{{ $check['source'] }}</code>@isset($check['hint'])<span class="text-slate-400">{{ $check['hint'] }}</span>@endisset</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $check['help'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 pl-14 sm:flex-col sm:items-end sm:gap-1.5 sm:pl-0">
                            @if ($check['ok'])
                                <x-admin.badge tone="green">Configured</x-admin.badge>
                            @else
                                <x-admin.badge tone="amber">Not configured</x-admin.badge>
                            @endif
                            @isset($check['link'])
                                @php($external = ! str_starts_with($check['link'], url('/')))
                                <a href="{{ $check['link'] }}" @if ($external) target="_blank" rel="noopener" @endif class="inline-flex items-center gap-1 text-sm font-semibold text-brand-blue-700 hover:underline">
                                    {{ $check['action'] }}<x-ui.icon :name="$external ? 'external' : 'arrow-right'" class="size-3.5" />@if ($external)<span class="sr-only"> (opens in a new tab)</span>@endif
                                </a>
                            @endisset
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="border-t border-admin-line px-5 py-3 text-xs text-slate-500 sm:px-6">Keys are read from the server's <code class="rounded bg-slate-100 px-1 py-0.5">.env</code> file (see <code class="rounded bg-slate-100 px-1 py-0.5">docs/DEPLOYMENT.md</code>). Their values are never shown here.</p>
        </section>

        {{-- Recent booking requests --}}
        <section class="admin-panel" aria-labelledby="recent-title">
            <div class="admin-panel-header">
                <div>
                    <h2 id="recent-title" class="admin-panel-title"><x-ui.icon name="inbox" class="size-5 text-admin-green" /> Recent Booking Requests</h2>
                    <p class="admin-panel-subtitle">The latest requests from your customers.</p>
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline !min-h-9 !px-3.5 !py-1.5">View all <x-ui.icon name="arrow-right" class="size-4" /></a>
            </div>

            @if ($recentBookings->isEmpty())
                <div class="flex flex-col items-center px-6 py-12 text-center">
                    <span class="relative flex size-24 items-center justify-center" aria-hidden="true">
                        <span class="absolute inset-0 rounded-full bg-admin-accent"></span>
                        <span class="absolute inset-3 rounded-full bg-white shadow-sm"></span>
                        <x-ui.icon name="calendar" class="relative size-10 text-brand-blue-500" />
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-admin-navy">No booking requests yet</h3>
                    <p class="mt-1 max-w-xs text-sm text-slate-500">When customers send a request from the website, it will appear here.</p>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-primary mt-5">View all bookings <x-ui.icon name="arrow-right" class="size-4" /></a>
                </div>
            @else
                <ul class="divide-y divide-admin-line">
                    @foreach ($recentBookings as $booking)
                        <li>
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="flex items-start gap-4 px-5 py-4 transition hover:bg-[#f9fbfe] sm:px-6">
                                <span class="admin-tile size-10 bg-admin-accent text-brand-blue-700"><x-ui.icon :name="$booking->booking_type->icon()" class="size-5" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="font-mono text-sm font-semibold text-admin-navy">{{ $booking->reference }}</span>
                                        <span class="text-sm text-slate-600">{{ $booking->customer_name }}</span>
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-500">
                                        {{ $booking->booking_type->label() }} · {{ $booking->pickup }}@if ($booking->dropoff) → {{ $booking->dropoff }}@endif
                                    </span>
                                    <span class="mt-0.5 flex items-center gap-1 text-xs text-slate-500">
                                        <x-ui.icon name="clock" class="size-3.5" />
                                        {{ $booking->travel_date->format('D, j M Y') }}, {{ \Illuminate\Support\Carbon::createFromFormat('H:i', $booking->pickup_time)->format('g:i A') }}
                                    </span>
                                </span>
                                <x-admin.badge :tone="$booking->statusTone()" class="shrink-0">{{ $booking->statusLabel() }}</x-admin.badge>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.admin>
