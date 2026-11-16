@use('App\Enums\BookingType')
@use('App\Fares\Money')
@php($type = $fare->booking_type)

<x-layouts.admin :title="$vehicle->name.' — '.$type->label()">
    <div class="grid gap-8 xl:grid-cols-[1fr_26rem]">
        <form method="POST" class="card space-y-6 p-6 sm:p-8" action="{{ $fare->exists ? route('admin.fares.update', $fare) : route('admin.fares.store') }}">
            @csrf
            @if ($fare->exists)
                @method('PUT')
            @else
                <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                <input type="hidden" name="booking_type" value="{{ $type->value }}">
            @endif

            @if ($type === BookingType::Rental)
            <fieldset>
                <legend class="text-lg font-bold text-brand-blue-950">Rental pricing</legend>
                <p class="mt-1 text-sm text-muted">Rentals are priced by package (beside the form). Customers can book a rental only once it is approved and has at least one active package.</p>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <x-admin.field name="rate_per_km" label="Extra km rate" :value="$fare->rate_per_km" prefix="₹" suffix="/km" inputmode="decimal" help="Charged beyond a package's included km. Optional." />
                    <x-admin.field name="extra_hour_rate" label="Extra hour rate" :value="$fare->extra_hour_rate" prefix="₹" suffix="/hr" inputmode="decimal" help="Charged beyond a package's hours. Optional." />
                </div>
            </fieldset>
            @else
            <fieldset>
                <legend class="text-lg font-bold text-brand-blue-950">Distance pricing</legend>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <x-admin.field name="rate_per_km" label="Rate per km" :value="$fare->rate_per_km" prefix="₹" suffix="/km" inputmode="decimal" required help="e.g. 13 or 13.50" />
                    <x-admin.field name="min_billable_km" label="Minimum billable distance" :value="$fare->min_billable_km" suffix="km" inputmode="decimal"
                        help="Trips shorter than this are billed at this distance. Leave blank for none." />
                    @if ($type->isOutstation())
                        <x-admin.field name="min_km_per_day" label="Minimum km per day" :value="$fare->min_km_per_day" suffix="km" inputmode="decimal"
                            help="Billable km is at least this × travel days (a one-way trip is one day). Leave blank for none." />
                    @endif
                    <x-admin.field name="minimum_fare" label="Minimum fare" :value="$fare->minimum_fare" prefix="₹" inputmode="decimal" help="The fare is never lower than this. Leave blank for none." />
                </div>

                @if ($type === BookingType::OutstationRoundTrip)
                    <fieldset class="mt-5">
                        <legend class="form-label">Return journey billing <span class="text-red-600" aria-hidden="true">*</span></legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach (['both_ways' => ['Onward + return distance', 'Billable distance = route distance × 2'], 'one_way_only' => ['One-way distance only', 'Return leg is not billed by distance']] as $value => [$label, $help])
                                <label class="choice-card py-3">
                                    <input type="radio" name="round_trip_distance" value="{{ $value }}" class="size-4 accent-brand-green-600" @checked(old('round_trip_distance', $fare->round_trip_distance) === $value)>
                                    <span><span class="block font-semibold text-brand-blue-950">{{ $label }}</span><span class="block text-sm text-muted">{{ $help }}</span></span>
                                </label>
                            @endforeach
                        </div>
                        <x-form.field-error name="round_trip_distance" />
                    </fieldset>
                @endif
            </fieldset>
            @endif

            <fieldset class="border-t border-line pt-6">
                <legend class="text-lg font-bold text-brand-blue-950">Additional charges</legend>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <x-admin.field name="driver_allowance_per_day" label="Driver allowance per day" :value="$fare->driver_allowance_per_day" prefix="₹" inputmode="decimal" help="Added once per travel day. Leave blank for none." />
                    <x-admin.field name="waiting_charge_per_hour" label="Waiting charge per hour" :value="$fare->waiting_charge_per_hour" prefix="₹" inputmode="decimal" help="Shown as a note; not added to estimates." />
                    <x-admin.field name="extra_charge" label="Other fixed charge" :value="$fare->extra_charge" prefix="₹" inputmode="decimal" help="Optional, added to every estimate." />
                    <x-admin.field name="extra_charge_label" label="Other charge description" :value="$fare->extra_charge_label" maxlength="80" help="e.g. Night charge" />
                </div>
                <fieldset class="mt-5">
                    <legend class="form-label">Tolls and parking</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach (['extra' => 'Extra — paid separately', 'included' => 'Included in the fare'] as $value => $label)
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <input type="radio" name="tolls_parking" value="{{ $value }}" class="size-4 accent-brand-green-600" @checked(old('tolls_parking', $fare->tolls_parking) === $value)> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </fieldset>

            <fieldset class="space-y-4 border-t border-line pt-6">
                <legend class="text-lg font-bold text-brand-blue-950">Status</legend>
                <x-admin.switch name="is_active" label="Active" :checked="$fare->is_active" help="Inactive fares hide this vehicle for this booking type." />
                <x-admin.switch name="is_approved" label="Approved tariff" :checked="$fare->is_approved" help="Customers can book this vehicle for this trip type only once the fare is approved. Leave off while the rate is a proposal." />
                @if ($fare->exists && ($issue = $fare->bookingIssue()))
                    <p class="rounded-xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">Not bookable by customers yet: {{ $issue }}.</p>
                @elseif ($fare->exists)
                    <p class="rounded-xl bg-brand-green-50 p-3 text-sm text-brand-green-800 ring-1 ring-brand-green-200">Customers can book {{ $vehicle->name }} for {{ $type->label() }}{{ $vehicle->is_active ? '' : ' once the vehicle is activated' }}.</p>
                @endif
                <x-admin.field name="effective_from" label="Effective from (optional)" :value="$fare->effective_from?->format('Y-m-d')" type="date" help="The fare applies from this date." />
                <div>
                    <label for="admin-notes" class="form-label">Internal notes</label>
                    <textarea id="admin-notes" name="notes" rows="2" maxlength="1000" class="form-control">{{ old('notes', $fare->notes) }}</textarea>
                </div>
            </fieldset>

            <div class="flex flex-wrap gap-3 border-t border-line pt-6">
                <button type="submit" class="btn btn-primary">{{ $fare->exists ? 'Save fare' : 'Create fare' }}</button>
                <a href="{{ route('admin.fares.index') }}" class="btn btn-outline">Back to fares</a>
            </div>
        </form>

        <aside aria-labelledby="preview-title">
            <div class="card p-6">
                <h2 id="preview-title" class="text-lg font-bold text-brand-blue-950">Fare preview</h2>
                @if ($preview && $type === BookingType::Rental)
                    <p class="mt-1 text-sm text-muted">Customers choose one of the active packages below. Calculated from the <strong>saved</strong> settings.</p>
                    @if ($preview['packages']->isEmpty())
                        <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                            No packages yet. This vehicle is not offered for rentals until you add at least one active package.
                        </p>
                    @else
                        <ul class="mt-4 divide-y divide-line text-sm">
                            @foreach ($preview['packages'] as $row)
                                <li class="flex justify-between gap-3 py-2">
                                    <span @class(['text-muted line-through' => ! $row['package']->is_active])>{{ $row['package']->name }}</span>
                                    <span class="font-semibold">{{ Money::format($row['estimate']->total) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <ul class="mt-3 list-disc space-y-1 pl-5 text-xs text-muted">
                            @foreach ($preview['packages']->first()['estimate']->notes as $note)<li>{{ $note }}</li>@endforeach
                        </ul>
                    @endif
                </div>

                <div class="card mt-6 p-6" id="packages">
                    <h2 class="text-lg font-bold text-brand-blue-950">Rental packages</h2>
                    <div class="mt-4 space-y-4">
                        @foreach ($fare->packages as $package)
                            <form method="POST" action="{{ route('admin.fares.packages.update', [$fare, $package]) }}" class="rounded-xl border border-line p-4">
                                @csrf @method('PUT')
                                <div class="grid grid-cols-2 gap-3">
                                    <input name="name" value="{{ $package->name }}" required maxlength="60" class="form-control col-span-2" aria-label="Package name">
                                    <input name="hours" value="{{ $package->hours }}" required inputmode="decimal" class="form-control" aria-label="Included hours" title="Included hours">
                                    <input name="included_km" value="{{ $package->included_km }}" inputmode="decimal" class="form-control" aria-label="Included km (blank = no km limit)" title="Included km (blank = no km limit)" placeholder="No km limit">
                                    <input name="price" value="{{ $package->price }}" required inputmode="decimal" class="form-control" aria-label="Price in rupees" title="Price (₹)">
                                    <input name="sort_order" value="{{ $package->sort_order }}" inputmode="numeric" class="form-control" aria-label="Display order" title="Display order">
                                    <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($package->is_active) class="size-4 accent-brand-green-600"> Active</label>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button type="submit" class="btn btn-outline !min-h-9 !px-4 !py-1.5 text-sm">Save</button>
                                    <button type="submit" form="delete-package-{{ $package->id }}" class="btn !min-h-9 !px-4 !py-1.5 text-sm text-red-700 hover:bg-red-50">Delete</button>
                                </div>
                            </form>
                            <form id="delete-package-{{ $package->id }}" method="POST" action="{{ route('admin.fares.packages.destroy', [$fare, $package]) }}"
                                data-confirm="Delete the {{ $package->name }} package?" onsubmit="return confirm(this.dataset.confirm)">
                                @csrf @method('DELETE')
                            </form>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.fares.packages.store', $fare) }}" class="mt-5 rounded-xl bg-surface p-4">
                        @csrf
                        <h3 class="text-sm font-bold text-brand-blue-950">Add a package</h3>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="col-span-2"><label for="pkg-name" class="form-label">Name</label><input id="pkg-name" name="name" required maxlength="60" placeholder="e.g. 4 hours" class="form-control"></div>
                            <div><label for="pkg-hours" class="form-label">Included hours</label><input id="pkg-hours" name="hours" required inputmode="decimal" class="form-control"></div>
                            <div><label for="pkg-km" class="form-label">Included km</label><input id="pkg-km" name="included_km" inputmode="decimal" placeholder="No km limit" class="form-control"></div>
                            <div><label for="pkg-price" class="form-label">Price (₹)</label><input id="pkg-price" name="price" required inputmode="decimal" class="form-control"></div>
                            <div><label for="pkg-order" class="form-label">Display order</label><input id="pkg-order" name="sort_order" inputmode="numeric" placeholder="0" class="form-control"></div>
                            <label class="col-span-2 flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked class="size-4 accent-brand-green-600"> Active</label>
                        </div>
                        <p class="form-help">Leave included km blank for packages without a km limit. Extra km and extra hours are charged at the rates set above, when configured.</p>
                        <button type="submit" class="btn btn-primary mt-4">Add package</button>
                    </form>
                @elseif ($preview)
                    <p class="mt-1 text-sm text-muted">Calculated from the <strong>saved</strong> fare. Save changes to update.</p>
                    <form method="GET" action="{{ route('admin.fares.edit', $fare) }}" class="mt-4 flex flex-wrap items-end gap-3">
                        <div class="w-28">
                            <label for="sample_km" class="form-label">Distance</label>
                            <input id="sample_km" name="sample_km" type="number" step="0.1" min="0" value="{{ $preview['sample_km'] }}" class="form-control" placeholder="km">
                        </div>
                        @if ($type === BookingType::OutstationRoundTrip)
                            <div class="w-24">
                                <label for="days" class="form-label">Days</label>
                                <input id="days" name="days" type="number" min="1" max="30" value="{{ $preview['days'] }}" class="form-control">
                            </div>
                        @endif
                        <button type="submit" class="btn btn-outline">Calculate</button>
                    </form>

                    <table class="mt-5 w-full text-sm">
                        <caption class="sr-only">Sample fares</caption>
                        <thead class="text-left text-xs text-muted uppercase">
                            <tr><th scope="col" class="py-2">{{ $type === BookingType::OutstationRoundTrip ? 'One-way km' : 'Route km' }}</th><th scope="col" class="py-2">Billable</th><th scope="col" class="py-2 text-right">Estimate</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($preview['rows'] as $row)
                                <tr @class(['bg-brand-blue-50 font-semibold' => (string) $row['km'] === (string) $preview['sample_km']])>
                                    <td class="py-2">{{ $row['km'] }}</td>
                                    <td class="py-2">{{ $row['estimate']->billableKmTenths !== null ? Money::formatKm($row['estimate']->billableKmTenths).' km' : '—' }}</td>
                                    <td class="py-2 text-right">{{ $row['estimate']->total !== null ? Money::format($row['estimate']->total) : 'To confirm' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @php($detail = $preview['rows']->first()['estimate'])
                    @if ($detail->hasTotal())
                        <h3 class="mt-6 text-sm font-bold text-brand-blue-950">Breakdown for {{ $preview['rows']->first()['km'] }} km</h3>
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($detail->lines as $line)
                                <li class="flex justify-between gap-3"><span class="text-muted">{{ $line['label'] }}</span><span>{{ Money::format($line['paise']) }}</span></li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-5 space-y-2 rounded-xl bg-surface p-3 text-sm">
                        @unless ($fare->is_approved)
                            <p class="font-semibold text-amber-900">Admin preview only — customers see no amount until this fare is approved.</p>
                        @endunless
                        <p><strong>Customers see, with a route distance:</strong> “{{ $detail->headline() }}”</p>
                        <p><strong>Distance unknown:</strong> “{{ $preview['unknown']->headline() }}”</p>
                    </div>
                    @if ($fare->is_approved && ($preview['unknown']->details || $preview['unknown']->notes))
                        <ul class="mt-3 list-disc space-y-1 pl-5 text-xs text-muted" aria-label="Fare details shown to customers">
                            @foreach ([...$preview['unknown']->details, ...$preview['unknown']->notes] as $note)<li>{{ $note }}</li>@endforeach
                        </ul>
                    @endif
                @else
                    <p class="mt-2 text-sm text-muted">Create the fare to see sample calculations.</p>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.admin>
