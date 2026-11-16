<?php

namespace Database\Factories;

use App\Enums\BookingType;
use App\Models\FareRule;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * For automated tests only.
 *
 * @extends Factory<FareRule>
 */
class FareRuleFactory extends Factory
{
    protected $model = FareRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'booking_type' => BookingType::OutstationOneWay->value,
            'rate_per_km' => '13.00',
            'min_billable_km' => null,
            'tolls_parking' => FareRule::TOLLS_EXTRA,
            'is_approved' => false,
            'is_active' => true,
        ];
    }
}
