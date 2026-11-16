<x-layouts.admin title="Vehicle models">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-2xl text-muted">
            Customers see a vehicle for a trip type only when the vehicle is <strong>active</strong> and its fare for that type is
            <strong>active and approved</strong> (rentals also need at least one active package).
        </p>
        <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary"><x-ui.icon name="car" class="size-4" /> Add vehicle</a>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($vehicles as $vehicle)
            <article class="card flex flex-col overflow-hidden">
                <div class="flex h-36 items-center justify-center bg-gradient-to-br from-brand-blue-50 to-brand-green-50 px-6">
                    @if ($vehicle->imageUrl())
                        <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" class="h-28 w-auto max-w-full object-contain" loading="lazy">
                    @else
                        <x-vehicle-art :category="$vehicle->category" class="h-24 w-auto" />
                    @endif
                </div>
                <div class="flex flex-1 flex-col p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-brand-blue-950">{{ $vehicle->name }}</h2>
                            <p class="text-sm text-muted">{{ $vehicle->categoryLabel() }} · Order {{ $vehicle->sort_order }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.vehicles.toggle', $vehicle) }}">
                            @csrf @method('PATCH')
                            <button type="submit" title="Click to {{ $vehicle->is_active ? 'deactivate' : 'activate' }}" aria-label="{{ $vehicle->is_active ? 'Deactivate' : 'Activate' }} {{ $vehicle->name }}">
                                @if ($vehicle->is_active)<x-admin.badge tone="green">Active</x-admin.badge>@else<x-admin.badge>Inactive</x-admin.badge>@endif
                            </button>
                        </form>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <div><dt class="text-muted">Passengers</dt><dd class="font-semibold">{{ $vehicle->passenger_capacity ?? 'Not set' }}</dd></div>
                        <div><dt class="text-muted">Luggage</dt><dd class="font-semibold">{{ $vehicle->luggage_capacity !== null ? $vehicle->luggage_capacity.' bags' : 'Not set' }}</dd></div>
                    </dl>
                    <x-admin.vehicle-availability :vehicle="$vehicle" :booking-types="$bookingTypes" class="mt-4 border-t border-line pt-4" />
                    <div class="mt-auto flex flex-wrap gap-3 pt-5">
                        <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="btn btn-outline !px-4">View<span class="sr-only"> {{ $vehicle->name }}</span></a>
                        <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-outline !px-4">Edit<span class="sr-only"> {{ $vehicle->name }}</span></a>
                        <a href="{{ route('admin.fares.index') }}#vehicle-{{ $vehicle->id }}" class="btn btn-outline !px-4">Fares</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="card p-8 md:col-span-2 xl:col-span-3">
                <h2 class="text-lg font-bold text-brand-blue-950">No vehicles yet</h2>
                <p class="mt-1 text-muted">Add your first vehicle, then add and approve a fare for each trip type you offer. Until then the booking form shows no vehicles.</p>
                <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary mt-4">Add vehicle</a>
            </div>
        @endforelse
    </div>
    <p class="mt-4 text-sm text-muted">Click a status badge to activate or deactivate a vehicle.</p>
</x-layouts.admin>
