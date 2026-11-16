<x-layouts.admin title="Booking requests">

    <p class="max-w-3xl text-muted">
        Ride requests submitted on the website. Every request starts as <strong>New request</strong> and is only
        confirmed when you change its status. The site cannot tell whether the customer pressed Send in WhatsApp.
        @if ($newCount) <strong class="text-brand-blue-950">{{ $newCount }} new {{ \Illuminate\Support\Str::plural('request', $newCount) }}.</strong> @endif
    </p>

    <form method="GET" action="{{ route('admin.bookings.index') }}" class="card mt-6 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-6" role="search" aria-label="Filter booking requests">
        <div class="lg:col-span-2">
            <label for="filter-q" class="form-label">Search</label>
            <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Reference, name or mobile" class="form-control">
        </div>
        <div>
            <label for="filter-type" class="form-label">Type</label>
            <select id="filter-type" name="type" class="form-control">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-status" class="form-label">Status</label>
            <select id="filter-status" name="status" class="form-control">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-from" class="form-label">Travel from</label>
            <input id="filter-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
        </div>
        <div>
            <label for="filter-to" class="form-label">Travel to</label>
            <input id="filter-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
        </div>
        <div class="flex flex-wrap gap-3 sm:col-span-2 lg:col-span-6">
            <button type="submit" class="btn btn-primary"><x-ui.icon name="search" class="size-4" /> Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">Clear filters</a>
            @endif
        </div>
    </form>

    <div class="card relative mt-6 overflow-x-auto">
        <table class="w-full min-w-[52rem] text-left text-sm">
            <caption class="sr-only">Booking requests, newest first</caption>
            <thead class="border-b border-line bg-surface text-xs tracking-wide text-muted uppercase">
                <tr>
                    <th scope="col" class="px-4 py-3">Reference</th>
                    <th scope="col" class="px-4 py-3">Customer</th>
                    <th scope="col" class="px-4 py-3">Trip</th>
                    <th scope="col" class="px-4 py-3">Travel</th>
                    <th scope="col" class="px-4 py-3">Fare</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($bookings as $booking)
                    <tr>
                        <th scope="row" class="px-4 py-3 align-top whitespace-nowrap">
                            <span class="block font-mono font-semibold whitespace-nowrap text-brand-blue-950">{{ $booking->reference }}</span>
                            <span class="block text-xs text-muted">{{ $booking->created_at->timezone(config('site.booking.timezone'))->format('j M Y, g:i A') }}</span>
                        </th>
                        <td class="px-4 py-3 align-top">
                            <span class="block font-medium">{{ $booking->customer_name }}</span>
                            <span class="block text-muted">{{ $booking->customer_phone }}</span>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <span class="block font-medium">{{ $booking->booking_type->label() }}</span>
                            <span class="block max-w-64 truncate text-muted" title="{{ $booking->pickup }}{{ $booking->dropoff ? ' → '.$booking->dropoff : '' }}">{{ $booking->pickup }}{{ $booking->dropoff ? ' → '.$booking->dropoff : '' }}</span>
                            @if ($booking->vehicle_name)<span class="block text-xs text-muted">{{ $booking->vehicle_name }}</span>@endif
                        </td>
                        <td class="px-4 py-3 align-top whitespace-nowrap">
                            {{ $booking->travel_date->format('D, j M Y') }}<br><span class="text-muted">{{ \Illuminate\Support\Carbon::createFromFormat('H:i', $booking->pickup_time)->format('g:i A') }}</span>
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if ($booking->fare_amount !== null)
                                <span class="block font-medium">{{ \App\Fares\Money::format(\App\Fares\Money::toPaise($booking->fare_amount)) }}</span>
                                <span class="text-xs text-muted">Estimate</span>
                            @else
                                <span class="text-muted">To be confirmed</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            <x-admin.badge :tone="$booking->statusTone()">{{ $booking->statusLabel() }}</x-admin.badge>
                        </td>
                        <td class="px-4 py-3 text-right align-top">
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="font-semibold text-brand-blue-700 hover:underline">View<span class="sr-only"> booking {{ $booking->reference }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-muted">
                            {{ array_filter($filters) ? 'No booking requests match these filters.' : 'No booking requests yet.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($bookings->hasPages())
        <nav class="mt-6 flex items-center justify-between gap-3 text-sm" aria-label="Pagination">
            <span class="text-muted">Showing {{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} of {{ $bookings->total() }}</span>
            <span class="flex gap-2">
                @if ($bookings->onFirstPage())
                    <span class="btn btn-outline pointer-events-none opacity-40" aria-disabled="true">Previous</span>
                @else
                    <a href="{{ $bookings->previousPageUrl() }}" class="btn btn-outline" rel="prev">Previous</a>
                @endif
                @if ($bookings->hasMorePages())
                    <a href="{{ $bookings->nextPageUrl() }}" class="btn btn-outline" rel="next">Next</a>
                @else
                    <span class="btn btn-outline pointer-events-none opacity-40" aria-disabled="true">Next</span>
                @endif
            </span>
        </nav>
    @endif
</x-layouts.admin>
