<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingType;
use App\Fares\FareCalculator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FareRuleRequest;
use App\Models\FareRule;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FareRuleController extends Controller
{
    /** Sample one-way distances (km) used in the preview table. */
    private const PREVIEW_KM = [5, 13, 25, 60, 150, 300];

    /** Vehicle × booking type matrix. */
    public function index(): View
    {
        return view('admin.fares.index', [
            'vehicles' => Vehicle::query()->with('fareRules.packages')->orderBy('sort_order')->orderBy('name')->get(),
            'bookingTypes' => BookingType::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $vehicle = Vehicle::findOrFail($request->integer('vehicle'));
        $type = BookingType::from((string) $request->query('type'));

        abort_if($vehicle->fareRules()->where('booking_type', $type->value)->exists(), 409, 'A fare for this vehicle and booking type already exists.');

        return view('admin.fares.form', [
            'fare' => new FareRule([
                'vehicle_id' => $vehicle->id,
                'booking_type' => $type,
                'tolls_parking' => FareRule::TOLLS_EXTRA,
                'round_trip_distance' => $type === BookingType::OutstationRoundTrip ? FareRule::RETURN_BOTH_WAYS : null,
                'is_active' => true,
            ]),
            'vehicle' => $vehicle,
            'preview' => null,
        ]);
    }

    public function store(FareRuleRequest $request): RedirectResponse
    {
        $fare = FareRule::create($request->validated());

        return redirect()->route('admin.fares.edit', $fare)->with('status', 'Fare created. Check the preview below.');
    }

    public function edit(Request $request, FareRule $fare, FareCalculator $calculator): View
    {
        $fare->load('vehicle', 'packages');

        // Rentals are priced per package, not per sample distance.
        if ($fare->booking_type === BookingType::Rental) {
            return view('admin.fares.form', [
                'fare' => $fare,
                'vehicle' => $fare->vehicle,
                'preview' => [
                    'packages' => $fare->packages->map(fn ($package) => ['package' => $package, 'estimate' => $calculator->calculate($fare, null, 1, $package)]),
                    'unknown' => $calculator->calculate($fare, null),
                ],
            ]);
        }

        $sample = $request->validate([
            'sample_km' => ['nullable', 'numeric', 'decimal:0,1', 'min:0', 'max:5000'],
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);
        $days = (int) ($sample['days'] ?? 1);

        $rows = collect(self::PREVIEW_KM)
            ->when(isset($sample['sample_km']), fn ($kms) => $kms->prepend((float) $sample['sample_km'])->unique())
            ->map(fn ($km) => ['km' => $km, 'estimate' => $calculator->calculate($fare, (int) round($km * 1000), $days)]);

        return view('admin.fares.form', [
            'fare' => $fare,
            'vehicle' => $fare->vehicle,
            'preview' => [
                'rows' => $rows,
                'days' => $days,
                'sample_km' => $sample['sample_km'] ?? null,
                'unknown' => $calculator->calculate($fare, null, $days),
            ],
        ]);
    }

    public function update(FareRuleRequest $request, FareRule $fare): RedirectResponse
    {
        $fare->update($request->validated());

        return redirect()->route('admin.fares.edit', $fare)->with('status', 'Fare saved.');
    }

    public function toggle(FareRule $fare): RedirectResponse
    {
        $fare->update(['is_active' => ! $fare->is_active]);

        return back()->with('status', 'Fare '.($fare->is_active ? 'activated' : 'deactivated').'.');
    }
}
