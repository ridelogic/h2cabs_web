@use('App\Fares\Money')

<x-layouts.admin title="Fares">
    <p class="max-w-3xl text-muted">
        Each vehicle has a separate fare for every booking type. Customers can book a vehicle for a trip type only when that fare is
        <strong>active</strong> and <strong>approved</strong> (and, for rentals, has at least one active package). Fares marked
        <strong>proposed</strong> are hidden from customers until you approve them.
    </p>

    <div class="mt-6 space-y-6">
        @forelse ($vehicles as $vehicle)
            <section id="vehicle-{{ $vehicle->id }}" class="card overflow-hidden" aria-labelledby="vehicle-{{ $vehicle->id }}-title">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-surface px-5 py-4">
                    <h2 id="vehicle-{{ $vehicle->id }}-title" class="text-lg font-bold text-brand-blue-950">
                        {{ $vehicle->name }}
                        @unless ($vehicle->is_active)<x-admin.badge class="ml-2">Vehicle inactive</x-admin.badge>@endunless
                    </h2>
                    <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="text-sm font-semibold text-brand-blue-700 hover:underline">Edit vehicle</a>
                </header>
                <div class="relative overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-left text-sm">
                        <thead class="text-xs tracking-wide text-muted uppercase">
                            <tr>
                                <th scope="col" class="px-5 py-3">Booking type</th>
                                <th scope="col" class="px-5 py-3">Rate</th>
                                <th scope="col" class="px-5 py-3">Min. billable</th>
                                <th scope="col" class="px-5 py-3">Other</th>
                                <th scope="col" class="px-5 py-3">Status</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($bookingTypes as $type)
                                @php($fare = $vehicle->fareRules->firstWhere('booking_type', $type))
                                <tr>
                                    <th scope="row" class="px-5 py-3 font-semibold text-brand-blue-950">{{ $type->label() }}</th>
                                    @if ($fare)
                                        <td class="px-5 py-3 font-semibold">
                                            {{ $fare->rateLabel() }}
                                            @if ($type === \App\Enums\BookingType::Rental)<span class="block text-xs font-normal text-muted">{{ $fare->packages->count() }} {{ \Illuminate\Support\Str::plural('package', $fare->packages->count()) }}</span>@endif
                                        </td>
                                        <td class="px-5 py-3">
                                            {{ $fare->min_billable_km !== null ? Money::formatKm(Money::toTenths($fare->min_billable_km)).' km' : '—' }}
                                            @if ($fare->min_km_per_day !== null)<span class="block text-xs text-muted">{{ Money::formatKm(Money::toTenths($fare->min_km_per_day)) }} km/day</span>@endif
                                        </td>
                                        <td class="px-5 py-3 text-muted">
                                            @if ($fare->minimum_fare !== null) Min. fare {{ Money::format(Money::toPaise($fare->minimum_fare)) }}<br>@endif
                                            @if ($fare->driver_allowance_per_day !== null) Allowance {{ Money::format(Money::toPaise($fare->driver_allowance_per_day)) }}/day<br>@endif
                                            Tolls/parking {{ $fare->tolls_parking === 'included' ? 'included' : 'extra' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @if ($fare->is_active)<x-admin.badge tone="green">Active</x-admin.badge>@else<x-admin.badge>Off</x-admin.badge>@endif
                                                @if ($fare->is_approved)<x-admin.badge tone="blue">Approved</x-admin.badge>@else<x-admin.badge tone="amber">Proposed</x-admin.badge>@endif
                                            </div>
                                            @php($issue = $fare->bookingIssue())
                                            <p @class(['mt-1 text-xs', 'text-brand-green-700' => ! $issue && $vehicle->is_active, 'text-amber-800' => $issue || ! $vehicle->is_active])>
                                                {{ $issue ? 'Not bookable: '.$issue : ($vehicle->is_active ? 'Bookable by customers' : 'Not bookable: vehicle inactive') }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('admin.fares.edit', $fare) }}" class="font-semibold text-brand-blue-700 hover:underline">Edit & preview<span class="sr-only">: {{ $vehicle->name }} {{ $type->label() }}</span></a>
                                        </td>
                                    @else
                                        <td colspan="4" class="px-5 py-3 text-muted">No fare configured — not bookable for this type.</td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('admin.fares.create', ['vehicle' => $vehicle->id, 'type' => $type->value]) }}" class="font-semibold text-brand-green-700 hover:underline">Add fare</a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <p class="text-muted">Add a vehicle first.</p>
        @endforelse
    </div>
</x-layouts.admin>
