<x-layouts.admin :title="$vehicle->name">
    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="card overflow-hidden" aria-labelledby="vehicle-details-title">
            <div class="flex h-56 items-center justify-center bg-gradient-to-br from-brand-blue-50 to-brand-green-50 px-8">
                @if ($vehicle->imageUrl())
                    <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" class="h-48 w-auto max-w-full object-contain">
                @else
                    <x-vehicle-art :category="$vehicle->category" class="h-36 w-auto" />
                @endif
            </div>
            <div class="p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="vehicle-details-title" class="text-xl font-bold text-brand-blue-950">{{ $vehicle->name }}</h2>
                        <p class="text-muted">{{ $vehicle->categoryLabel() }}</p>
                    </div>
                    @if ($vehicle->is_active)<x-admin.badge tone="green">Active</x-admin.badge>@else<x-admin.badge>Inactive — hidden from customers</x-admin.badge>@endif
                </div>
                @if ($vehicle->description)<p class="mt-4 text-muted">{{ $vehicle->description }}</p>@endif
                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div><dt class="text-muted">Passengers</dt><dd class="font-semibold">{{ $vehicle->passenger_capacity ?? 'Not set' }}</dd></div>
                    <div><dt class="text-muted">Luggage</dt><dd class="font-semibold">{{ $vehicle->luggage_capacity !== null ? $vehicle->luggage_capacity.' bags' : 'Not set' }}</dd></div>
                    <div><dt class="text-muted">Display order</dt><dd class="font-semibold">{{ $vehicle->sort_order }}</dd></div>
                    <div><dt class="text-muted">URL key</dt><dd class="font-mono text-xs font-semibold">{{ $vehicle->slug }}</dd></div>
                    <div><dt class="text-muted">Booking requests</dt><dd class="font-semibold">{{ $bookingCount }}</dd></div>
                    <div><dt class="text-muted">Updated</dt><dd class="font-semibold">{{ $vehicle->updated_at?->timezone(config('site.booking.timezone'))->format('j M Y, g:i a') }}</dd></div>
                </dl>
                <div class="mt-6 flex flex-wrap gap-3 border-t border-line pt-6">
                    <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-primary">Edit vehicle</a>
                    <form method="POST" action="{{ route('admin.vehicles.toggle', $vehicle) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-outline">{{ $vehicle->is_active ? 'Deactivate' : 'Activate' }}</button>
                    </form>
                    <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline">All vehicles</a>
                </div>
            </div>
        </section>

        <aside class="card h-fit p-6" aria-labelledby="availability-title">
            <h2 id="availability-title" class="text-lg font-bold text-brand-blue-950">Booking availability</h2>
            <p class="mt-1 text-sm text-muted">A trip type is bookable when the vehicle is active and its fare is active, approved and complete.</p>
            <x-admin.vehicle-availability :vehicle="$vehicle" :booking-types="$bookingTypes" links class="mt-4" />
        </aside>
    </div>
</x-layouts.admin>
