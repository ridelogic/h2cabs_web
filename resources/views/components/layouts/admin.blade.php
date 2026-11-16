{{--
    Admin portal shell: navy sidebar (collapsible on desktop, a drawer on
    mobile), top bar and page header. Behaviour is in resources/js/admin.js.

    Props:
    - title:    page title (also the <title>)
    - subtitle: optional line under the title
    - header:   false to hide the standard page header (the dashboard draws its own)
    Slot `actions`: optional buttons beside the page title.
--}}
@props(['title', 'subtitle' => null, 'header' => true])

@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'dashboard'],
        ['label' => 'Booking Requests', 'route' => 'admin.bookings.index', 'active' => 'admin.bookings.*', 'icon' => 'calendar', 'badge' => $adminNewBookings ?? 0],
        ['label' => 'Members', 'route' => 'admin.members.index', 'active' => 'admin.members.*', 'icon' => 'users'],
        ['label' => 'Vehicles', 'route' => 'admin.vehicles.index', 'active' => 'admin.vehicles.*', 'icon' => 'car'],
        ['label' => 'Fares & Pricing', 'route' => 'admin.fares.index', 'active' => 'admin.fares.*', 'icon' => 'receipt'],
        ['label' => 'Settings', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'icon' => 'settings'],
        ['label' => 'SEO Management', 'route' => 'admin.seo.index', 'active' => 'admin.seo.*', 'icon' => 'search'],
    ];
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'A';
    $newBookings = $adminNewBookings ?? 0;
@endphp

<!DOCTYPE html>
<html lang="en-IN" data-sidebar="expanded">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title }} · H2 CABS Admin</title>
        <link rel="icon" href="{{ asset(config('site.brand.logo_mark')) }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap">
        {{-- Restore the collapsed sidebar before first paint (no layout jump). --}}
        <script>try { if (localStorage.getItem('h2c-admin-sidebar') === 'collapsed') document.documentElement.dataset.sidebar = 'collapsed'; } catch (e) {}</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-app min-h-screen" data-admin-shell>
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:font-semibold">Skip to content</a>

        {{-- Mobile drawer backdrop --}}
        <div class="fixed inset-0 z-40 bg-admin-navy-deep/50 backdrop-blur-sm lg:hidden" data-sidebar-backdrop hidden></div>

        <aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
            <div class="admin-brand flex h-16 shrink-0 lg:h-[70px] items-center gap-3 border-b border-white/10 px-6">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg shadow-black/20">
                    <img src="{{ asset(config('site.brand.logo_mark')) }}" alt="" class="size-full">
                </span>
                <span class="admin-collapse-hide leading-tight">
                    <span class="block text-lg font-extrabold tracking-tight">H2 CABS</span>
                    <span class="block text-xs font-medium text-white/60">Admin Portal</span>
                </span>
                <button type="button" class="admin-icon-btn ml-auto text-white/70 hover:bg-white/10 hover:text-white lg:hidden" data-sidebar-close aria-label="Close menu">
                    <x-ui.icon name="close" class="size-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-2.5 py-5" aria-label="Admin">
                <p class="admin-collapse-hide px-3.5 pb-2 text-[0.6875rem] font-semibold tracking-widest text-white/40 uppercase">Menu</p>
                <ul class="space-y-1">
                    @foreach ($nav as $item)
                        @php($active = request()->routeIs($item['active']))
                        <li>
                            <a href="{{ route($item['route']) }}" class="admin-nav-link" title="{{ $item['label'] }}" @if ($active) aria-current="page" @endif>
                                <x-ui.icon :name="$item['icon']" class="size-5" />
                                <span class="admin-collapse-hide flex-1">{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? 0) > 0)
                                    <span class="admin-collapse-hide flex min-w-5.5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-xs font-bold text-white shadow-sm shadow-red-900/30" aria-label="{{ $item['badge'] }} new">{{ $item['badge'] }}</span>
                                    {{-- Collapsed rail: a dot instead of the count. --}}
                                    <span class="absolute top-2 right-5 hidden size-2 rounded-full bg-red-500 ring-2 ring-admin-navy lg:in-data-[sidebar=collapsed]:block" aria-hidden="true"></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>

                <p class="admin-collapse-hide mt-6 px-3.5 pb-2 text-[0.6875rem] font-semibold tracking-widest text-white/40 uppercase">Website</p>
                <a href="{{ route('admin.pages.index') }}" class="admin-nav-link mb-1" title="Website Pages" @if (request()->routeIs('admin.pages.*')) aria-current="page" @endif>
                    <x-ui.icon name="file-text" class="size-5" />
                    <span class="admin-collapse-hide flex-1">Website Pages</span>
                </a>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-nav-link" title="View Website (opens in a new tab)">
                    <x-ui.icon name="globe" class="size-5" />
                    <span class="admin-collapse-hide flex-1">View Website</span>
                    <x-ui.icon name="external" class="admin-collapse-hide size-4 text-white/50" />
                </a>
            </nav>

            {{-- Dark seafront photo fading up into the navy (decorative). --}}
            <div class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-72 overflow-hidden" aria-hidden="true">
                <img src="{{ asset('images/photos/promenade-hero-1280.webp') }}" alt="" class="size-full object-cover object-[62%_center] opacity-30 mix-blend-luminosity [mask-image:linear-gradient(to_top,black_10%,transparent)]" loading="lazy" width="1280" height="853">
                <div class="absolute inset-0 bg-gradient-to-t from-admin-navy-deep/70 via-admin-navy/40 to-transparent"></div>
            </div>

            <div class="shrink-0 border-t border-white/10 p-3">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="admin-nav-link w-full" title="Log out">
                        <x-ui.icon name="log-out" class="size-5" />
                        <span class="admin-collapse-hide">Log out</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="flex h-16 items-center gap-2 px-4 sm:gap-3 sm:px-6 lg:h-[70px] lg:px-8">
                    <button type="button" class="admin-icon-btn lg:hidden" data-sidebar-open aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open menu">
                        <x-ui.icon name="menu" class="size-5" />
                    </button>
                    <button type="button" class="admin-icon-btn hidden lg:inline-flex" data-sidebar-collapse aria-controls="admin-sidebar" aria-expanded="true" aria-label="Collapse sidebar" title="Collapse or expand the sidebar">
                        <x-ui.icon name="panel-left" class="size-5" />
                    </button>

                    {{-- Global search: bookings, members, vehicles and fares (Admin\SearchController). --}}
                    <form method="GET" action="{{ route('admin.search') }}" role="search" class="relative min-w-0 flex-1 sm:max-w-lg">
                        <label for="admin-search" class="sr-only">Search bookings, members, vehicles and fares</label>
                        <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                        <input id="admin-search" type="search" name="q" maxlength="100" value="{{ request()->routeIs('admin.search', 'admin.bookings.index') ? request('q') : '' }}"
                            placeholder="Search bookings, members, vehicles..."
                            class="h-10 w-full rounded-xl border border-admin-line bg-admin-bg pr-3 pl-10 text-sm text-ink placeholder:text-slate-400 transition focus:border-brand-blue-500 focus:bg-white focus:ring-4 focus:ring-brand-blue-100 focus:outline-none">
                    </form>

                    <div class="ml-auto flex items-center gap-1 sm:gap-2">
                        {{-- Notifications: the latest new (unhandled) booking requests. --}}
                        <details class="relative" data-admin-menu>
                            <summary class="admin-icon-btn cursor-pointer list-none [&::-webkit-details-marker]:hidden"
                                title="{{ $newBookings ? $newBookings.' new booking '.\Illuminate\Support\Str::plural('request', $newBookings) : 'No new booking requests' }}">
                                <x-ui.icon name="bell" class="size-5" />
                                <span class="sr-only">Notifications. New booking requests: {{ $newBookings }}</span>
                                @if ($newBookings > 0)
                                    <span class="absolute top-1 right-1 flex min-w-4.5 items-center justify-center rounded-full bg-red-500 px-1 text-[0.625rem] leading-4.5 font-bold text-white ring-2 ring-white" aria-hidden="true">{{ $newBookings > 99 ? '99+' : $newBookings }}</span>
                                @endif
                            </summary>
                            <div class="fixed inset-x-3 top-16 z-40 overflow-hidden rounded-2xl border border-admin-line bg-white shadow-admin-hover sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-96">
                                <div class="flex items-center justify-between border-b border-admin-line px-4 py-3">
                                    <p class="text-sm font-bold text-admin-navy">Notifications</p>
                                    @if ($newBookings > 0)<x-admin.badge tone="amber">{{ $newBookings }} new</x-admin.badge>@endif
                                </div>
                                {{-- Filled from admin.notifications when the menu opens (admin.js). --}}
                                <div data-notifications data-src="{{ route('admin.notifications') }}" aria-live="polite">
                                    <p class="px-4 py-8 text-center text-sm text-slate-500">
                                        {{ $newBookings ? $newBookings.' new booking '.\Illuminate\Support\Str::plural('request', $newBookings).' waiting.' : 'No new booking requests.' }}
                                    </p>
                                </div>
                                <a href="{{ route('admin.bookings.index', ['status' => 'new']) }}" class="flex items-center justify-center gap-1 border-t border-admin-line px-4 py-3 text-sm font-semibold text-brand-blue-700 hover:bg-admin-accent">
                                    View all new requests <x-ui.icon name="arrow-right" class="size-3.5" />
                                </a>
                            </div>
                        </details>

                        <details class="relative" data-admin-menu>
                            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl py-1.5 pr-2 pl-1.5 transition hover:bg-admin-accent [&::-webkit-details-marker]:hidden">
                                <span class="flex size-9 items-center justify-center rounded-full bg-gradient-to-br from-admin-navy to-brand-blue-600 text-sm font-bold text-white" aria-hidden="true">{{ $initials }}</span>
                                <span class="hidden text-left leading-tight md:block">
                                    <span class="block max-w-40 truncate text-sm font-semibold text-admin-navy">{{ $user?->name }}</span>
                                    <span class="block text-xs text-slate-500">Administrator</span>
                                </span>
                                <x-ui.icon name="chevron-down" class="hidden size-4 text-slate-400 md:block" />
                                <span class="sr-only">Account menu</span>
                            </summary>
                            <div class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-2xl border border-admin-line bg-white shadow-admin-hover">
                                <div class="border-b border-admin-line px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-admin-navy">{{ $user?->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $user?->email }}</p>
                                </div>
                                <div class="p-1.5 text-sm">
                                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-slate-700 hover:bg-admin-accent">
                                        <x-ui.icon name="globe" class="size-4" /> View website
                                    </a>
                                    <a href="{{ route('admin.settings.edit') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-slate-700 hover:bg-admin-accent">
                                        <x-ui.icon name="settings" class="size-4" /> Website settings
                                    </a>
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-red-700 hover:bg-red-50">
                                            <x-ui.icon name="log-out" class="size-4" /> Log out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </header>

            <main id="main" tabindex="-1" class="mx-auto w-full max-w-[90rem] px-4 py-6 focus:outline-none sm:px-6 sm:py-8 lg:px-8">
                @if ($header)
                    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="text-2xl font-extrabold tracking-tight text-admin-navy sm:text-[1.75rem]">{{ $title }}</h1>
                            @if ($subtitle)<p class="mt-1 text-sm text-slate-500 sm:text-base">{{ $subtitle }}</p>@endif
                        </div>
                        @isset($actions)<div class="flex flex-wrap gap-3">{{ $actions }}</div>@endisset
                    </div>
                @endif

                @if (session('status'))
                    <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-admin-green text-white"><x-ui.icon name="check" class="size-3.5" /></span>
                        <span class="pt-0.5">{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <x-ui.icon name="alert" class="mt-0.5 size-5 shrink-0" />
                        <div>
                            <p class="font-semibold">Please fix the following:</p>
                            <ul class="mt-1 list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
