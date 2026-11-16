{{--
    Outstation fare rules as actually configured in the admin dashboard —
    nothing here is hardcoded. Unset values say "confirmed when you book".
--}}
@props(['detailed' => false])

@php
    use App\Enums\BookingType;
    use App\Fares\Money;
    use App\Models\FareRule;

    try {
        $vehicles = \App\Models\Vehicle::query()->active()->with('fareRules')->get()
            ->filter(fn ($v) => $v->fareFor(BookingType::OutstationOneWay) || $v->fareFor(BookingType::OutstationRoundTrip))
            ->values();
    } catch (\Illuminate\Database\QueryException) {
        $vehicles = collect();
    }

    $money = fn ($value) => $value !== null ? Money::format(Money::toPaise($value)) : null;
    $km = fn ($value) => $value !== null ? Money::formatKm(Money::toTenths($value)).' km' : null;
    $rules = $vehicles->flatMap(fn ($v) => array_filter([$v->fareFor(BookingType::OutstationOneWay), $v->fareFor(BookingType::OutstationRoundTrip)]));
    $anyProposed = $rules->contains(fn (FareRule $r) => ! $r->is_approved);
    // Only approved rules may inform what customers are told.
    $approved = $rules->filter(fn (FareRule $r) => $r->is_approved);
    $allowances = $approved->pluck('driver_allowance_per_day')->filter()->unique();
    $tollsIncluded = $approved->isNotEmpty() && $approved->every(fn (FareRule $r) => $r->tolls_parking === FareRule::TOLLS_INCLUDED);
    $roundTripPolicies = $approved->filter(fn (FareRule $r) => $r->booking_type === BookingType::OutstationRoundTrip)->pluck('round_trip_distance')->filter()->unique();
@endphp

<div {{ $attributes }}>
    @if ($vehicles->isNotEmpty())
        <div class="relative overflow-x-auto rounded-2xl border border-line bg-white">
            <table class="w-full min-w-[30rem] text-left text-sm">
                <caption class="sr-only">Outstation rates by vehicle</caption>
                <thead class="bg-surface text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-4 py-3">Vehicle</th>
                        <th scope="col" class="px-4 py-3">One way</th>
                        <th scope="col" class="px-4 py-3">Round trip</th>
                        <th scope="col" class="px-4 py-3">Min. billable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($vehicles as $vehicle)
                        @php($ow = $vehicle->fareFor(BookingType::OutstationOneWay))
                        @php($rt = $vehicle->fareFor(BookingType::OutstationRoundTrip))
                        <tr>
                            <th scope="row" class="px-4 py-3 font-bold text-brand-blue-950">{{ $vehicle->name }}</th>
                            <td class="px-4 py-3">{{ $ow ? $ow->publicRateLabel() : 'Not offered' }}</td>
                            <td class="px-4 py-3">{{ $rt ? $rt->publicRateLabel() : 'Not offered' }}</td>
                            @php($approvedRule = collect([$ow, $rt])->first(fn ($r) => $r?->is_approved && $r->min_billable_km !== null))
                            <td class="px-4 py-3">{{ $approvedRule ? $km($approvedRule->min_billable_km) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($anyProposed)
            <p class="mt-2 text-xs text-muted">Rates are shown once approved. Otherwise H2 CABS confirms the fare with you before the trip.</p>
        @endif
    @endif

    <ul class="mt-5 grid gap-3 text-sm {{ $detailed ? 'sm:grid-cols-2' : '' }}">
        <li class="flex gap-3"><x-ui.icon name="road" class="size-5 shrink-0 text-brand-green-600" /><span><strong>Rate per km</strong> for your vehicle, applied to the road distance.</span></li>
        <li class="flex gap-3"><x-ui.icon name="target" class="size-5 shrink-0 text-brand-green-600" /><span><strong>Minimum billable distance</strong> — short trips are billed at the minimum.</span></li>
        <li class="flex gap-3"><x-ui.icon name="repeat" class="size-5 shrink-0 text-brand-green-600" /><span><strong>Round trips:</strong>
            @if ($roundTripPolicies->count() === 1 && $roundTripPolicies->first() === FareRule::RETURN_BOTH_WAYS)
                the onward and return distance are both counted.
            @elseif ($roundTripPolicies->count() === 1 && $roundTripPolicies->first() === FareRule::RETURN_ONE_WAY_ONLY)
                only the one-way distance is billed by the kilometre.
            @else
                return-journey billing is confirmed when you book.
            @endif
        </span></li>
        <li class="flex gap-3"><x-ui.icon name="user" class="size-5 shrink-0 text-brand-green-600" /><span><strong>Driver allowance:</strong>
            {{ $allowances->count() === 1 ? $money($allowances->first()).' per day.' : ($allowances->isEmpty() ? 'confirmed when you book, if applicable.' : 'varies by vehicle.') }}
        </span></li>
        <li class="flex gap-3"><x-ui.icon name="receipt" class="size-5 shrink-0 text-brand-green-600" /><span><strong>Tolls, parking & permits:</strong> {{ $tollsIncluded ? 'included.' : 'extra, paid as applicable.' }}</span></li>
        <li class="flex gap-3"><x-ui.icon name="clock" class="size-5 shrink-0 text-brand-green-600" /><span><strong>Waiting time</strong> may be charged if the trip runs longer than planned.</span></li>
    </ul>
</div>
