<?php

namespace App\Fares;

use App\Enums\BookingType;
use App\Models\FareRule;
use App\Models\RentalPackage;

/**
 * The single place where fares are calculated (booking estimate, booking
 * record, WhatsApp message and admin preview all use it). Each booking
 * type is priced only by its own fare rule.
 *
 * Local and Outstation (distance-based):
 *   trip km       = route distance (round trips: doubled only if the rule
 *                   bills the return journey "both ways")
 *   billable km   = max(trip km, min billable km, min km per day × days)
 *   distance fare = billable km × rate per km
 *   fare          = max(distance fare, minimum fare)
 *                   + driver allowance × days + fixed charge
 *
 * Rental (package-based):
 *   fare          = selected package price + driver allowance × days
 *                   + fixed charge; extra km / extra hours are listed as notes
 *
 * Amounts are always computed (for the admin preview), but FareEstimate
 * only shows them to customers when the rule is approved and the
 * calculation is complete. Nothing is invented: a missing rate, distance,
 * package or return policy results in "requires confirmation".
 */
class FareCalculator
{
    /**
     * @param  int|null  $distanceMeters  One-way route distance, or null when unknown.
     * @param  int  $days  Number of travel days (1 unless a round trip spans several days).
     * @param  RentalPackage|null  $package  The chosen package, for rentals.
     */
    public function calculate(FareRule $rule, ?int $distanceMeters, int $days = 1, ?RentalPackage $package = null): FareEstimate
    {
        $days = max(1, $days);
        $notes = $this->notes($rule);
        $details = $this->details($rule);
        [$fixed, $fixedTotal] = $this->fixedCharges($rule, $days);

        if ($rule->booking_type === BookingType::Rental) {
            return $this->rental($rule, $package, $fixed, $fixedTotal, $notes, $details);
        }

        $rate = Money::toPaise($rule->rate_per_km);

        if ($rate === null) {
            return new FareEstimate(null, null, null, [], null, null, $rule->is_approved, $notes, FareEstimate::REASON_NO_RATE, $details);
        }

        $minKm = Money::toTenths($rule->min_billable_km) ?? 0;
        $minKmPerDay = Money::toTenths($rule->min_km_per_day);
        $minimumFare = Money::toPaise($rule->minimum_fare);
        $floorKm = max($minKm, $minKmPerDay === null ? 0 : $minKmPerDay * $days);

        if ($rule->booking_type === BookingType::OutstationRoundTrip && ! in_array($rule->round_trip_distance, [FareRule::RETURN_BOTH_WAYS, FareRule::RETURN_ONE_WAY_ONLY], true)) {
            // No configured policy for the return leg: do not guess.
            return new FareEstimate($rate, null, null, [], null, null, $rule->is_approved, $notes, FareEstimate::REASON_NO_RETURN_POLICY, $details);
        }

        $tripKm = $this->tripTenths($rule, $distanceMeters);

        if ($tripKm === null) {
            // Admin preview only: the lowest possible fare for this rule.
            $floorFare = max($this->distanceFare($floorKm, $rate), $minimumFare ?? 0);
            $minimum = $floorFare > 0 ? $floorFare + $fixedTotal : null;

            return new FareEstimate($rate, null, null, [], null, $minimum, $rule->is_approved, $notes, FareEstimate::REASON_NO_DISTANCE, $details);
        }

        $billable = max($tripKm, $floorKm);
        $lines = [[
            'label' => Money::formatKm($billable).' km × '.Money::format($rate).'/km'.($billable > $tripKm ? ' (minimum billable distance)' : ''),
            'paise' => $this->distanceFare($billable, $rate),
        ]];

        if ($minimumFare !== null && $lines[0]['paise'] < $minimumFare) {
            $lines[] = ['label' => 'Minimum fare adjustment', 'paise' => $minimumFare - $lines[0]['paise']];
        }

        $lines = [...$lines, ...$fixed];

        return new FareEstimate($rate, $tripKm, $billable, $lines, array_sum(array_column($lines, 'paise')), null, $rule->is_approved, $notes, null, $details);
    }

    /**
     * @param  list<array{label: string, paise: int}>  $fixed
     * @param  list<string>  $notes
     * @param  list<string>  $details
     */
    private function rental(FareRule $rule, ?RentalPackage $package, array $fixed, int $fixedTotal, array $notes, array $details): FareEstimate
    {
        if ($package === null) {
            // Rentals are not priced per km, so no rate is passed.
            return new FareEstimate(null, null, null, [], null, null, $rule->is_approved, $notes, FareEstimate::REASON_NO_PACKAGE, $details);
        }

        $extraKm = Money::toPaise($rule->rate_per_km);
        $extraHour = Money::toPaise($rule->extra_hour_rate);

        if ($extraKm !== null && $package->included_km !== null) {
            $notes[] = 'Beyond '.Money::formatKm(Money::toTenths($package->included_km)).' km: '.Money::format($extraKm).' per km.';
        }

        if ($extraHour !== null) {
            $notes[] = 'Beyond '.Money::formatKm(Money::toTenths($package->hours)).' hours: '.Money::format($extraHour).' per hour.';
        }

        $lines = [['label' => $package->name, 'paise' => Money::toPaise($package->price)], ...$fixed];

        return new FareEstimate(null, null, null, $lines, Money::toPaise($package->price) + $fixedTotal, null, $rule->is_approved, $notes, null, $details);
    }

    /**
     * @return array{0: list<array{label: string, paise: int}>, 1: int}
     */
    private function fixedCharges(FareRule $rule, int $days): array
    {
        $fixed = [];

        if ($allowance = Money::toPaise($rule->driver_allowance_per_day)) {
            $fixed[] = ['label' => $days > 1 ? "Driver allowance ({$days} days)" : 'Driver allowance', 'paise' => $allowance * $days];
        }

        if ($extra = Money::toPaise($rule->extra_charge)) {
            $fixed[] = ['label' => $rule->extra_charge_label ?: 'Additional charge', 'paise' => $extra];
        }

        return [$fixed, array_sum(array_column($fixed, 'paise'))];
    }

    private function tripTenths(FareRule $rule, ?int $distanceMeters): ?int
    {
        if ($distanceMeters === null) {
            return null;
        }

        $oneWay = Money::divideRounded($distanceMeters, 100);

        return $rule->booking_type === BookingType::OutstationRoundTrip && $rule->round_trip_distance === FareRule::RETURN_BOTH_WAYS
            ? $oneWay * 2
            : $oneWay;
    }

    private function distanceFare(int $kmTenths, int $ratePaise): int
    {
        return Money::divideRounded($kmTenths * $ratePaise, 10);
    }

    /**
     * Configured pricing facts, appropriate to the booking type. Only values
     * that are actually set appear; nothing is assumed.
     *
     * @return list<string>
     */
    private function details(FareRule $rule): array
    {
        $details = [];
        $money = fn ($value) => Money::format(Money::toPaise($value));
        $km = fn ($value) => Money::formatKm(Money::toTenths($value)).' km';

        if ($rule->booking_type->isDistanceBased()) {
            if ($rule->rate_per_km !== null) {
                $details[] = 'Rate: '.$money($rule->rate_per_km).' per km';
            }
            if ($rule->min_billable_km !== null) {
                $details[] = 'Minimum billable distance: '.$km($rule->min_billable_km);
            }
            if ($rule->booking_type->isOutstation() && $rule->min_km_per_day !== null) {
                $details[] = 'Minimum distance per day: '.$km($rule->min_km_per_day);
            }
            if ($rule->minimum_fare !== null) {
                $details[] = 'Minimum fare: '.$money($rule->minimum_fare);
            }
        }

        if ($rule->driver_allowance_per_day !== null) {
            $details[] = 'Driver allowance: '.$money($rule->driver_allowance_per_day).' per day';
        }

        if ($rule->extra_charge !== null) {
            $details[] = ($rule->extra_charge_label ?: 'Additional charge').': '.$money($rule->extra_charge);
        }

        return $details;
    }

    /**
     * Conditions that apply to the fare (shown only once it is approved).
     *
     * @return list<string>
     */
    private function notes(FareRule $rule): array
    {
        $notes = [];

        if ($rule->booking_type === BookingType::OutstationRoundTrip && $rule->round_trip_distance === FareRule::RETURN_BOTH_WAYS) {
            $notes[] = 'Round trip: the onward and return distances are both counted.';
        }

        $notes[] = $rule->tolls_parking === FareRule::TOLLS_INCLUDED
            ? 'Tolls and parking are included.'
            : 'Tolls and parking charges, if any, are paid separately.';

        if ($waiting = Money::toPaise($rule->waiting_charge_per_hour)) {
            $notes[] = 'Waiting time is charged at '.Money::format($waiting).' per hour, if applicable.';
        }

        return $notes;
    }
}
