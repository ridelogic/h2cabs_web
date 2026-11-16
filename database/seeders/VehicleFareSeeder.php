<?php

namespace Database\Seeders;

use App\Enums\BookingType;
use App\Models\FareRule;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Vehicles and PROPOSED fares.
 *
 * Source: the reference website's js/h2cabs-config.js, where these rates
 * are explicitly labelled "Placeholder starting prices". They are seeded
 * with is_approved = false, so customers are NOT offered these vehicles
 * until an admin approves each fare (Admin → Fares).
 *
 * - Sedan ₹13/km and SUV ₹14/km are active (the agreed starting point).
 * - Premium ₹16/km and Larger vehicle ₹18/km are listed in the reference but
 *   seeded inactive for the association to review.
 * - Local and both Outstation types: ₹/km as above, minimum billable distance
 *   13 km (proposed). Rental: its own rule with no rate and no packages
 *   (fare to be confirmed) — never copied from another type.
 * - Minimum billable distance: 13 km (proposed). No minimum fare, driver
 *   allowance, waiting charge or extra charge is set — none were documented.
 * - Round trips bill the onward and return distance ("both_ways"); this is
 *   a visible, editable policy, not an assumption hidden in code.
 *
 * Idempotent: vehicles match on slug and fares on (vehicle, booking type);
 * existing rows — including admin edits — are never overwritten.
 */
class VehicleFareSeeder extends Seeder
{
    public const PROPOSED_MIN_KM = '13.0';

    /** slug => [name, category, passengers, luggage, rate per km, active] */
    public const VEHICLES = [
        'sedan' => ['Sedan', 'sedan', 4, 2, '13.00', true],
        'suv' => ['SUV', 'suv', 6, 3, '14.00', true],
        'premium' => ['Premium', 'premium', 4, 2, '16.00', false],
        'larger-vehicle' => ['Larger Vehicle', 'large', 7, 4, '18.00', false],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::VEHICLES as $slug => [$name, $category, $passengers, $luggage, $rate, $active]) {
            $vehicle = Vehicle::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'category' => $category,
                // Capacities as listed on the reference website.
                'passenger_capacity' => $passengers,
                'luggage_capacity' => $luggage,
                'is_active' => $active,
                'sort_order' => $order += 10,
            ]);

            foreach (BookingType::cases() as $type) {
                // Rental has its own pricing (packages) and never copies a per-km fare.
                $attributes = $type === BookingType::Rental
                    ? [
                        'rate_per_km' => null,
                        'notes' => 'Rental pricing not configured yet — add rental packages. Customers see "fare to be confirmed".',
                    ]
                    : [
                        'rate_per_km' => $rate,
                        'min_billable_km' => self::PROPOSED_MIN_KM,
                        'round_trip_distance' => $type === BookingType::OutstationRoundTrip ? FareRule::RETURN_BOTH_WAYS : null,
                        'notes' => 'Proposed rate from the reference website (placeholder pricing). Review before approving.',
                    ];

                FareRule::firstOrCreate(
                    ['vehicle_id' => $vehicle->id, 'booking_type' => $type->value],
                    [...$attributes, 'tolls_parking' => FareRule::TOLLS_EXTRA, 'is_approved' => false, 'is_active' => $active],
                );
            }
        }
    }
}
