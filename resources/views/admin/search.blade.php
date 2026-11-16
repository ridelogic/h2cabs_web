@php
    $groups = [
        'bookings' => ['title' => 'Booking Requests', 'icon' => 'calendar', 'tile' => 'bg-violet-50 text-violet-600', 'all' => route('admin.bookings.index', ['q' => $term])],
        'members' => ['title' => 'Members', 'icon' => 'users', 'tile' => 'bg-admin-accent text-brand-blue-700', 'all' => route('admin.members.index')],
        'vehicles' => ['title' => 'Vehicles', 'icon' => 'car', 'tile' => 'bg-emerald-50 text-admin-green', 'all' => route('admin.vehicles.index')],
        'fares' => ['title' => 'Fares & Pricing', 'icon' => 'receipt', 'tile' => 'bg-amber-50 text-amber-600', 'all' => route('admin.fares.index')],
    ];
@endphp

<x-layouts.admin title="Search" :subtitle="$term === '' ? 'Search booking requests, members, vehicles and fares.' : $total.' '.\Illuminate\Support\Str::plural('result', $total).' for “'.$term.'”'">
    @if ($results === null)
        <div class="admin-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="admin-tile size-16 bg-admin-accent text-brand-blue-600"><x-ui.icon name="search" class="size-8" /></span>
            <h2 class="mt-4 text-lg font-bold text-admin-navy">What are you looking for?</h2>
            <p class="mt-1 max-w-sm text-sm text-slate-500">Type a booking reference, customer name or mobile, a member's name, a vehicle or a trip type in the search bar above.</p>
        </div>
    @elseif ($total === 0)
        <div class="admin-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="admin-tile size-16 bg-slate-100 text-slate-500"><x-ui.icon name="search" class="size-8" /></span>
            <h2 class="mt-4 text-lg font-bold text-admin-navy">No matches found</h2>
            <p class="mt-1 max-w-sm text-sm text-slate-500">Nothing matched “{{ $term }}”. Check the spelling or try a shorter term.</p>
        </div>
    @else
        <div class="grid items-start gap-6 lg:grid-cols-2 [&>*]:min-w-0">
            @foreach ($groups as $key => $group)
                @continue($results[$key]->isEmpty())
                <section class="admin-panel" aria-labelledby="results-{{ $key }}">
                    <div class="admin-panel-header items-center">
                        <h2 id="results-{{ $key }}" class="admin-panel-title">
                            <span class="admin-tile size-9 {{ $group['tile'] }}"><x-ui.icon :name="$group['icon']" class="size-5" /></span>
                            {{ $group['title'] }}
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $results[$key]->count() }}</span>
                        </h2>
                        <a href="{{ $group['all'] }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-blue-700 hover:underline">View all <x-ui.icon name="arrow-right" class="size-3.5" /></a>
                    </div>
                    <ul class="divide-y divide-admin-line">
                        @foreach ($results[$key] as $item)
                            <li>
                                @switch($key)
                                    @case('bookings')
                                        <a href="{{ route('admin.bookings.show', $item) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-[#f9fbfe] sm:px-6">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-admin-navy"><span class="font-mono">{{ $item->reference }}</span> · {{ $item->customer_name }}</span>
                                                <span class="block truncate text-xs text-slate-500">{{ $item->booking_type->label() }} · {{ $item->pickup }}@if ($item->dropoff) → {{ $item->dropoff }}@endif</span>
                                            </span>
                                            <x-admin.badge :tone="$item->statusTone()" class="shrink-0">{{ $item->statusLabel() }}</x-admin.badge>
                                        </a>
                                        @break
                                    @case('members')
                                        <a href="{{ route('admin.members.edit', $item) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-[#f9fbfe] sm:px-6">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-admin-navy">{{ $item->name }}</span>
                                                <span class="block truncate text-xs text-slate-500">{{ $item->role ?: $item->groupLabel() }}</span>
                                            </span>
                                            @if ($item->is_active)<x-admin.badge tone="green" class="shrink-0">Active</x-admin.badge>@else<x-admin.badge class="shrink-0">Inactive</x-admin.badge>@endif
                                        </a>
                                        @break
                                    @case('vehicles')
                                        <a href="{{ route('admin.vehicles.show', $item) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-[#f9fbfe] sm:px-6">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-admin-navy">{{ $item->name }}</span>
                                                <span class="block truncate text-xs text-slate-500">{{ $item->categoryLabel() }}</span>
                                            </span>
                                            @if ($item->is_active)<x-admin.badge tone="green" class="shrink-0">Active</x-admin.badge>@else<x-admin.badge class="shrink-0">Inactive</x-admin.badge>@endif
                                        </a>
                                        @break
                                    @case('fares')
                                        <a href="{{ route('admin.fares.edit', $item) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-[#f9fbfe] sm:px-6">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-admin-navy">{{ $item->vehicle?->name }} · {{ $item->booking_type->label() }}</span>
                                                <span class="block truncate text-xs text-slate-500">{{ $item->rateLabel() }}</span>
                                            </span>
                                            @if (! $item->is_active)
                                                <x-admin.badge class="shrink-0">Inactive</x-admin.badge>
                                            @elseif ($item->is_approved)
                                                <x-admin.badge tone="green" class="shrink-0">Approved</x-admin.badge>
                                            @else
                                                <x-admin.badge tone="amber" class="shrink-0">Proposed</x-admin.badge>
                                            @endif
                                        </a>
                                        @break
                                @endswitch
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
