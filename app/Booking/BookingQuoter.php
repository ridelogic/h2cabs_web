<?php

namespace App\Booking;

use App\Enums\BookingType;
use App\Fares\FareCalculator;
use App\Models\FareRule;
use App\Models\RentalPackage;
use App\Models\Vehicle;
use App\Places\PlaceSearchException;
use App\Places\RouteDistance;
use Illuminate\Support\Carbon;

/**
 * Server-side fare quote for a booking request. The browser never supplies
 * a distance or price: distance comes from the configured route provider,
 * and the fare from the selected vehicle's fare rule for that booking type.
 */
class BookingQuoter
{
    public function __construct(
        private readonly RouteDistance $distance,
        private readonly FareCalculator $calculator,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated booking input.
     */
    public function quote(array $data): BookingQuote
    {
        $type = BookingType::from($data['booking_type']);
        $vehicle = Vehicle::query()->with('fareRules.packages')->find($data['vehicle_id'] ?? null);
        $rule = $vehicle?->fareFor($type);
        $package = $rule ? $this->package($rule, $data['rental_package_id'] ?? null) : null;

        $meters = $type->isDistanceBased() && $this->hasRoute($data) ? $this->measure($data) : null;
        $days = $this->days($type, $data);

        return new BookingQuote(
            type: $type,
            vehicle: $vehicle,
            package: $package,
            distanceMeters: $meters,
            days: $days,
            estimate: $rule ? $this->calculator->calculate($rule, $meters, $days, $package) : null,
            distanceAvailable: $this->distance->available(),
        );
    }

    /** Only an active package of this vehicle's rental rule can be used. */
    private function package(FareRule $rule, mixed $packageId): ?RentalPackage
    {
        if ($rule->booking_type !== BookingType::Rental || blank($packageId)) {
            return null;
        }

        return $rule->packages->first(fn (RentalPackage $package) => $package->is_active && $package->id === (int) $packageId);
    }

    private function hasRoute(array $data): bool
    {
        return filled($data['pickup'] ?? null) && filled($data['dropoff'] ?? null);
    }

    private function measure(array $data): ?int
    {
        if (! $this->distance->available()) {
            return null;
        }

        try {
            return $this->distance->meters(
                ['text' => $data['pickup'], 'place_id' => $data['pickup_place_id'] ?? null],
                ['text' => $data['dropoff'], 'place_id' => $data['dropoff_place_id'] ?? null],
            );
        } catch (PlaceSearchException) {
            return null;
        }
    }

    private function days(BookingType $type, array $data): int
    {
        if ($type !== BookingType::OutstationRoundTrip || blank($data['travel_date'] ?? null) || blank($data['return_date'] ?? null)) {
            return 1;
        }

        $days = Carbon::parse($data['travel_date'])->diffInDays(Carbon::parse($data['return_date'])) + 1;

        return (int) max(1, min($days, 31));
    }
}
