<?php

namespace App\Booking;

use App\Enums\BookingType;
use App\Fares\FareEstimate;
use App\Fares\Money;
use App\Models\RentalPackage;
use App\Models\Vehicle;

final class BookingQuote
{
    public function __construct(
        public readonly BookingType $type,
        public readonly ?Vehicle $vehicle,
        public readonly ?RentalPackage $package,
        public readonly ?int $distanceMeters,
        public readonly int $days,
        public readonly ?FareEstimate $estimate,
        public readonly bool $distanceAvailable,
    ) {}

    /** e.g. "152.3 km (approx. one way by road)" */
    public function distanceLabel(): ?string
    {
        return self::formatDistance($this->distanceMeters, $this->type);
    }

    public static function formatDistance(?int $meters, BookingType $type): ?string
    {
        if ($meters === null) {
            return null;
        }

        $km = Money::formatKm(Money::divideRounded($meters, 100));

        return $km.' km (approx. '.($type === BookingType::OutstationRoundTrip ? 'one way ' : '').'by road)';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'distance' => $this->distanceLabel(),
            'distance_available' => $this->distanceAvailable,
            'days' => $this->days,
            'estimate' => $this->estimate?->toArray(),
            'summary' => $this->estimate?->summaryLine(),
        ];
    }
}
