<?php

namespace App\Enums;

/**
 * The four independent booking types. Each has its own fare rule per
 * vehicle (see App\Models\FareRule); none inherits another's pricing.
 *
 * - Local: point-to-point trips within the local service area.
 * - Rental: a car and driver for a package or duration.
 * - Outstation one way / round trip: intercity journeys.
 */
enum BookingType: string
{
    case Local = 'local';
    case Rental = 'rental';
    case OutstationOneWay = 'outstation_one_way';
    case OutstationRoundTrip = 'outstation_round_trip';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local',
            self::Rental => 'Rental',
            self::OutstationOneWay => 'Outstation One Way',
            self::OutstationRoundTrip => 'Outstation Round Trip',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Local => 'Point to point in town',
            self::Rental => 'Car & driver by the hour',
            self::OutstationOneWay => 'Drop at another city',
            self::OutstationRoundTrip => 'Go and come back',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Local => 'pin',
            self::Rental => 'clock',
            self::OutstationOneWay => 'arrow-right',
            self::OutstationRoundTrip => 'repeat',
        };
    }

    public function isOutstation(): bool
    {
        return $this === self::OutstationOneWay || $this === self::OutstationRoundTrip;
    }

    /** Distance-based (per-km) pricing. Rental is priced by package instead. */
    public function isDistanceBased(): bool
    {
        return $this !== self::Rental;
    }

    /** Rentals may follow an itinerary that ends where it started. */
    public function requiresDropoff(): bool
    {
        return $this !== self::Rental;
    }

    public function requiresReturnDate(): bool
    {
        return $this === self::OutstationRoundTrip;
    }
}
