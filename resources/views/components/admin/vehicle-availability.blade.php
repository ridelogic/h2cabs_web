{{--
    Per booking type: whether customers can book this vehicle, and if not,
    why (see FareRule::bookingIssue()). Expects fareRules.packages loaded.
    An active vehicle with no bookable trip type gets a note explaining that it
    stays out of the website booking form until it has an approved fare.
--}}
@props(['vehicle', 'bookingTypes', 'links' => false])

@php
    $bookableAny = collect($bookingTypes)->contains(fn ($type) => $vehicle->fareFor($type));
@endphp

<div {{ $attributes }}>
    <ul class="space-y-1.5 text-sm">
        @foreach ($bookingTypes as $type)
            @php
                $fare = $vehicle->configuredFareFor($type);
                $issue = $fare?->bookingIssue();
            @endphp
            <li class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-muted">{{ $type->label() }}</span>
                <span class="flex items-center gap-2">
                    @if ($fare && $issue === null)
                        <span class="font-semibold">{{ $fare->rateLabel() }}</span>
                        <x-admin.badge :tone="$vehicle->is_active ? 'green' : 'gray'">{{ $vehicle->is_active ? 'Bookable' : 'Vehicle inactive' }}</x-admin.badge>
                    @elseif ($fare)
                        <x-admin.badge tone="amber">{{ $issue }}</x-admin.badge>
                    @else
                        <x-admin.badge>No fare</x-admin.badge>
                    @endif
                    @if ($links)
                        <a href="{{ $fare ? route('admin.fares.edit', $fare) : route('admin.fares.create', ['vehicle' => $vehicle->id, 'type' => $type->value]) }}"
                            class="font-semibold text-brand-blue-700 hover:underline">{{ $fare ? 'Edit' : 'Add fare' }}<span class="sr-only">: {{ $vehicle->name }} {{ $type->label() }}</span></a>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>

    @if ($vehicle->is_active && ! $bookableAny)
        <p class="mt-3 flex gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200" role="note">
            <x-ui.icon name="info" class="mt-0.5 size-4 shrink-0" />
            <span>
                <strong>Not in the website booking form yet.</strong>
                {{ $vehicle->name }} is listed on the website, but customers can only choose it when it has an active, approved fare for a trip type.
                <a href="{{ route('admin.fares.index') }}#vehicle-{{ $vehicle->id }}" class="font-semibold underline">Configure its fare in Fares &amp; Pricing</a>.
            </span>
        </p>
    @endif
</div>
