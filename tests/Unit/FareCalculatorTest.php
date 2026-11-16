<?php

namespace Tests\Unit;

use App\Enums\BookingType;
use App\Fares\FareCalculator;
use App\Fares\FareEstimate;
use App\Fares\Money;
use App\Models\FareRule;
use App\Models\RentalPackage;
use Tests\TestCase;

class FareCalculatorTest extends TestCase
{
    private FareCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new FareCalculator;
    }

    private function rule(array $attributes = []): FareRule
    {
        return new FareRule(array_merge([
            'booking_type' => BookingType::OutstationOneWay->value,
            'rate_per_km' => '13.00',
            'min_billable_km' => '13.0',
            'tolls_parking' => FareRule::TOLLS_EXTRA,
            'is_approved' => false,
            'is_active' => true,
        ], $attributes));
    }

    public function test_sedan_rate_applies_to_distance_above_the_minimum(): void
    {
        $estimate = $this->calculator->calculate($this->rule(), 20_000);

        $this->assertSame(200, $estimate->tripKmTenths);
        $this->assertSame(200, $estimate->billableKmTenths);
        $this->assertSame(26_000, $estimate->total); // 20 km × ₹13 = ₹260
    }

    public function test_suv_rate_is_configured_separately(): void
    {
        $estimate = $this->calculator->calculate($this->rule(['rate_per_km' => '14.00']), 20_000);

        $this->assertSame(28_000, $estimate->total); // 20 km × ₹14 = ₹280
    }

    public function test_minimum_billable_distance_is_applied_to_short_trips(): void
    {
        $estimate = $this->calculator->calculate($this->rule(), 5_000);

        $this->assertSame(50, $estimate->tripKmTenths);
        $this->assertSame(130, $estimate->billableKmTenths);
        $this->assertSame(16_900, $estimate->total); // max(5, 13) km × ₹13 = ₹169
        $this->assertStringContainsString('minimum billable distance', $estimate->lines[0]['label']);
    }

    public function test_rate_and_minimum_kilometres_are_independent(): void
    {
        // ₹13/km with a 20 km minimum, and ₹20/km with a 13 km minimum, give different results.
        $this->assertSame(26_000, $this->calculator->calculate($this->rule(['min_billable_km' => '20']), 5_000)->total);
        $this->assertSame(26_000, $this->calculator->calculate($this->rule(['rate_per_km' => '20']), 5_000)->total);
        $this->assertSame(6_500, $this->calculator->calculate($this->rule(['min_billable_km' => null]), 5_000)->total);
    }

    public function test_money_is_exact_with_fractional_rates_and_distances(): void
    {
        // 152.3 km × ₹13.35 = ₹2,033.205 → ₹2,033.21 (half up, in paise)
        $estimate = $this->calculator->calculate($this->rule(['rate_per_km' => '13.35']), 152_300);

        $this->assertSame(203_321, $estimate->total);
        $this->assertSame('₹2,033.21', Money::format($estimate->total));
    }

    public function test_minimum_fare_is_applied_as_a_separate_line(): void
    {
        $estimate = $this->calculator->calculate($this->rule(['minimum_fare' => '300']), 5_000);

        $this->assertSame(30_000, $estimate->total);
        $this->assertSame('Minimum fare adjustment', $estimate->lines[1]['label']);
        $this->assertSame(13_100, $estimate->lines[1]['paise']);
    }

    public function test_driver_allowance_and_extra_charges_are_added_when_configured(): void
    {
        $estimate = $this->calculator->calculate($this->rule([
            'booking_type' => BookingType::OutstationRoundTrip->value,
            'round_trip_distance' => FareRule::RETURN_BOTH_WAYS,
            'driver_allowance_per_day' => '300',
            'extra_charge' => '50',
            'extra_charge_label' => 'Night charge',
        ]), 100_000, 2);

        // 100 km × 2 = 200 km × ₹13 = ₹2,600 + ₹300 × 2 days + ₹50
        $this->assertSame(260_000 + 60_000 + 5_000, $estimate->total);
        $this->assertContains('Night charge', array_column($estimate->lines, 'label'));
        $this->assertContains('Driver allowance (2 days)', array_column($estimate->lines, 'label'));
    }

    public function test_round_trip_counts_return_distance_only_when_configured(): void
    {
        $roundTrip = ['booking_type' => BookingType::OutstationRoundTrip->value];

        $both = $this->calculator->calculate($this->rule([...$roundTrip, 'round_trip_distance' => FareRule::RETURN_BOTH_WAYS]), 150_000);
        $oneWay = $this->calculator->calculate($this->rule([...$roundTrip, 'round_trip_distance' => FareRule::RETURN_ONE_WAY_ONLY]), 150_000);
        $unset = $this->calculator->calculate($this->rule([...$roundTrip, 'round_trip_distance' => null, 'is_approved' => true]), 150_000);

        $this->assertSame(3000, $both->billableKmTenths);
        $this->assertSame(1500, $oneWay->billableKmTenths);
        $this->assertNull($unset->total, 'Without a return policy the fare must not be guessed.');
        $this->assertSame(FareEstimate::REASON_NO_RETURN_POLICY, $unset->confirmationReason());
        $this->assertSame('Fare will be confirmed by H2 CABS.', $unset->headline());
    }

    public function test_minimum_km_per_day_scales_with_trip_days(): void
    {
        $estimate = $this->calculator->calculate($this->rule([
            'booking_type' => BookingType::OutstationRoundTrip->value,
            'round_trip_distance' => FareRule::RETURN_BOTH_WAYS,
            'min_km_per_day' => '250',
        ]), 50_000, 3);

        $this->assertSame(1000, $estimate->tripKmTenths);
        $this->assertSame(7500, $estimate->billableKmTenths); // 250 km × 3 days
    }

    public function test_local_trips_are_never_doubled(): void
    {
        $estimate = $this->calculator->calculate($this->rule([
            'booking_type' => BookingType::Local->value,
            'round_trip_distance' => FareRule::RETURN_BOTH_WAYS,
        ]), 20_000);

        $this->assertSame(200, $estimate->billableKmTenths);
    }

    public function test_a_rule_without_a_rate_gives_fare_to_be_confirmed(): void
    {
        $estimate = $this->calculator->calculate($this->rule(['booking_type' => BookingType::Local->value, 'rate_per_km' => null, 'is_approved' => true]), 20_000);

        $this->assertNull($estimate->total);
        $this->assertNull($estimate->minimum);
        $this->assertSame(FareEstimate::REASON_NO_RATE, $estimate->confirmationReason());
        $this->assertSame('Fare will be confirmed by H2 CABS', $estimate->summaryLine());
    }

    public function test_rental_is_priced_by_the_chosen_package_not_by_distance(): void
    {
        $rule = $this->rule(['booking_type' => BookingType::Rental->value, 'rate_per_km' => '15', 'extra_hour_rate' => '150', 'min_billable_km' => null]);
        $package = new RentalPackage(['name' => '4 hours', 'hours' => '4', 'included_km' => '40', 'price' => '1200', 'is_active' => true]);

        $estimate = $this->calculator->calculate($rule, 90_000, 1, $package);

        $this->assertSame(120_000, $estimate->total, 'Distance must not change a package price.');
        $this->assertNull($estimate->billableKmTenths);
        $this->assertSame('4 hours', $estimate->lines[0]['label']);
        $this->assertContains('Beyond 40 km: ₹15 per km.', $estimate->notes);
        $this->assertContains('Beyond 4 hours: ₹150 per hour.', $estimate->notes);
    }

    public function test_rental_without_a_package_asks_for_one_and_shows_no_amount(): void
    {
        $estimate = $this->calculator->calculate($this->rule(['booking_type' => BookingType::Rental->value, 'rate_per_km' => null, 'is_approved' => true]), 50_000);

        $this->assertNull($estimate->total);
        $this->assertSame(FareEstimate::REASON_NO_PACKAGE, $estimate->confirmationReason());
        $this->assertSame('Choose a rental package to see the estimate.', $estimate->headline());
        $this->assertNull($estimate->toArray()['total']);
    }

    public function test_rental_extra_km_rate_is_never_presented_as_a_per_km_fare(): void
    {
        $rule = $this->rule(['booking_type' => BookingType::Rental->value, 'rate_per_km' => '15', 'extra_hour_rate' => '150']);

        $noPackage = $this->calculator->calculate($rule, 50_000);
        $this->assertSame('Fare will be confirmed by H2 CABS', $noPackage->summaryLine());
        $this->assertNull($noPackage->toArray()['total']);
        $this->assertSame([], $noPackage->toArray()['lines']);

        $package = new RentalPackage(['name' => '4 hours', 'hours' => '4', 'included_km' => '40', 'price' => '1200']);
        // Unapproved: no amount at all.
        $this->assertSame('Fare will be confirmed by H2 CABS', $this->calculator->calculate($rule, null, 1, $package)->summaryLine());

        $rule->is_approved = true;
        $approved = $this->calculator->calculate($rule, null, 1, $package);
        $this->assertSame('₹1,200 (estimated fare — not a confirmed quote)', $approved->summaryLine());
        $this->assertNotContains('Rate: ₹15 per km', $approved->details);
    }

    public function test_package_without_a_km_limit_does_not_mention_extra_km(): void
    {
        $rule = $this->rule(['booking_type' => BookingType::Rental->value, 'rate_per_km' => '15']);
        $package = new RentalPackage(['name' => 'Airport wait', 'hours' => '3', 'included_km' => null, 'price' => '700']);

        $notes = $this->calculator->calculate($rule, null, 1, $package)->notes;

        $this->assertEmpty(array_filter($notes, fn ($n) => str_contains($n, 'per km')));
    }

    public function test_rental_package_adds_configured_driver_allowance(): void
    {
        $rule = $this->rule(['booking_type' => BookingType::Rental->value, 'driver_allowance_per_day' => '250']);
        $package = new RentalPackage(['name' => 'Full day', 'hours' => '8', 'price' => '2000']);

        $this->assertSame(225_000, $this->calculator->calculate($rule, null, 1, $package)->total);
    }

    public function test_unknown_distance_never_shows_an_amount(): void
    {
        $unapproved = $this->calculator->calculate($this->rule(), null);
        $this->assertNull($unapproved->total);
        $this->assertSame('Fare will be confirmed by H2 CABS', $unapproved->summaryLine());

        $estimate = $this->calculator->calculate($this->rule(['is_approved' => true]), null);
        $this->assertNull($estimate->total);
        $this->assertSame(FareEstimate::REQUIRES_CONFIRMATION, $estimate->status());
        $this->assertSame(FareEstimate::REASON_NO_DISTANCE, $estimate->confirmationReason());
        $this->assertSame('Fare will be confirmed after the route is verified.', $estimate->headline());
        $this->assertStringNotContainsString('From', $estimate->summaryLine());
        $this->assertStringNotContainsString('₹', $estimate->summaryLine());

        // The configured facts can still be shown, because the rate is approved.
        $this->assertSame(['Rate: ₹13 per km', 'Minimum billable distance: 13 km'], $estimate->toArray()['details']);
        $this->assertNull($estimate->toArray()['total']);
    }

    public function test_approved_estimate_with_a_distance_is_labelled_as_not_a_quote(): void
    {
        $estimate = $this->calculator->calculate($this->rule(['is_approved' => true]), 20_000);

        $this->assertSame(FareEstimate::ESTIMATED, $estimate->status());
        $this->assertNull($estimate->confirmationReason());
        $this->assertSame('Estimated fare: ₹260 — not a confirmed quote', $estimate->headline());
        $this->assertSame('₹260 (estimated fare — not a confirmed quote)', $estimate->summaryLine());
        $this->assertSame('₹260', $estimate->toArray()['total']);
    }

    public function test_unapproved_estimate_hides_amounts_and_settings_from_customers(): void
    {
        $estimate = $this->calculator->calculate($this->rule(['waiting_charge_per_hour' => '100']), 20_000);

        // Calculated (for the admin preview) but never exposed publicly.
        $this->assertSame(26_000, $estimate->total);
        $this->assertSame(FareEstimate::REQUIRES_CONFIRMATION, $estimate->status());
        $this->assertSame(FareEstimate::REASON_UNAPPROVED, $estimate->confirmationReason());
        $this->assertSame('Fare will be confirmed by H2 CABS.', $estimate->headline());

        $public = $estimate->toArray();
        $this->assertNull($public['total']);
        $this->assertNull($public['billable_km']);
        $this->assertSame([], $public['lines']);
        $this->assertSame([], $public['details']);
        $this->assertSame([], $public['notes']);
        $this->assertStringNotContainsString('₹', json_encode($public, JSON_UNESCAPED_UNICODE));
    }

    public function test_notes_state_the_toll_policy(): void
    {
        $extra = $this->calculator->calculate($this->rule(['is_approved' => true]), 20_000);
        $included = $this->calculator->calculate($this->rule(['is_approved' => true, 'tolls_parking' => FareRule::TOLLS_INCLUDED]), 20_000);

        $this->assertContains('Tolls and parking charges, if any, are paid separately.', $extra->toArray()['notes']);
        $this->assertContains('Tolls and parking are included.', $included->toArray()['notes']);
        foreach ([$extra, $included] as $estimate) {
            $this->assertStringNotContainsStringIgnoringCase('proposed', $estimate->summaryLine().implode(' ', $estimate->notes));
        }
    }

    public function test_waiting_charge_is_noted_but_not_added(): void
    {
        $estimate = $this->calculator->calculate($this->rule(['waiting_charge_per_hour' => '100']), 20_000);

        $this->assertSame(26_000, $estimate->total);
        $this->assertContains('Waiting time is charged at ₹100 per hour, if applicable.', $estimate->notes);
    }
}
