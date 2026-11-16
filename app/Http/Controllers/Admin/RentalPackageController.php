<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RentalPackageRequest;
use App\Models\FareRule;
use App\Models\RentalPackage;
use Illuminate\Http\RedirectResponse;

/** Priced packages (e.g. "4 hrs / 40 km") on a vehicle's Rental fare. */
class RentalPackageController extends Controller
{
    public function store(RentalPackageRequest $request, FareRule $fare): RedirectResponse
    {
        $this->ensureRental($fare);

        $fare->packages()->create([...$request->validated(), 'sort_order' => $request->validated('sort_order') ?? 0]);

        return back()->with('status', 'Rental package added.');
    }

    public function update(RentalPackageRequest $request, FareRule $fare, RentalPackage $package): RedirectResponse
    {
        $this->ensureRental($fare);

        $package->update([...$request->validated(), 'sort_order' => $request->validated('sort_order') ?? $package->sort_order]);

        return back()->with('status', 'Rental package saved.');
    }

    public function destroy(FareRule $fare, RentalPackage $package): RedirectResponse
    {
        $this->ensureRental($fare);
        $package->delete();

        return back()->with('status', 'Rental package deleted.');
    }

    private function ensureRental(FareRule $fare): void
    {
        abort_unless($fare->booking_type === BookingType::Rental, 404);
    }
}
