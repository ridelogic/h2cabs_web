<?php

namespace Tests\Feature;

use App\Models\FareRule;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vehicles added in Admin → Vehicles appear on the public website straight
 * from the database. The admin Active toggle controls visibility; a vehicle
 * without a bookable fare is listed with "Fare available on request" but can
 * only be booked online once it has an active, approved fare.
 */
class PublicVehicleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function innova(array $attributes = []): Vehicle
    {
        return Vehicle::factory()->create([
            'name' => 'Innova crysta',
            'slug' => 'innova-crysta',
            'category' => 'suv',
            'image_path' => 'vehicles/innova-crysta.png',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function approvedLocalFare(Vehicle $vehicle, array $attributes = []): FareRule
    {
        return FareRule::factory()->for($vehicle)->create([
            'booking_type' => 'local',
            'rate_per_km' => '16.00',
            'is_approved' => true,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    public function test_an_active_vehicle_with_no_fare_appears_with_fare_on_request(): void
    {
        $this->innova();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Innova crysta')
            ->assertSee('src="'.asset('storage/vehicles/innova-crysta.png').'"', false)
            ->assertSee('Fare available on request')
            ->assertSee('Enquire about Innova crysta')
            ->assertDontSee('Select Innova crysta')
            ->assertDontSee('Vehicle options are being set up.');

        $this->get(route('services'))
            ->assertOk()
            ->assertSee(['Innova crysta', 'Fare available on request', 'Enquire about Innova crysta'])
            ->assertDontSee('Choose Innova crysta');
    }

    public function test_an_active_vehicle_whose_fare_is_not_approved_appears_without_a_rate(): void
    {
        $vehicle = $this->innova();
        $this->approvedLocalFare($vehicle, ['is_approved' => false]);

        $this->get(route('home'))
            ->assertSee('Innova crysta')
            ->assertSee('Fare available on request')
            ->assertDontSee('₹16/km')
            ->assertDontSee('Select Innova crysta');
    }

    public function test_a_vehicle_without_a_bookable_fare_cannot_be_booked(): void
    {
        $vehicle = $this->innova();

        // Listed in the booking form but disabled for every trip type.
        $html = $this->get(route('booking.create', ['vehicle' => 'innova-crysta']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#value="'.$vehicle->id.'"[^>]*disabled#s', $html);

        $this->post(route('booking.store'), [
            'booking_type' => 'local',
            'vehicle_id' => $vehicle->id,
            'pickup' => 'White Town, Puducherry',
            'dropoff' => 'Railway Station, Puducherry',
            'travel_date' => now('Asia/Kolkata')->addDays(2)->toDateString(),
            'pickup_time' => '10:00',
            'passengers' => 2,
            'name' => 'Test Customer',
            'phone' => '9876543210',
        ])->assertSessionHasErrors('vehicle_id');
    }

    public function test_an_active_vehicle_with_an_approved_fare_shows_its_rate_and_can_be_selected(): void
    {
        $vehicle = $this->innova();
        $this->approvedLocalFare($vehicle);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Select Innova crysta')
            ->assertSee('href="'.route('booking.create', ['vehicle' => 'innova-crysta']).'"', false)
            ->assertSee('₹16/km')
            ->assertDontSee('Enquire about Innova crysta');

        $this->get(route('services'))->assertOk()->assertSee('Choose Innova crysta');
    }

    public function test_homepage_vehicle_dropdown_lists_vehicles_with_a_fare_for_the_trip_type(): void
    {
        $vehicle = $this->innova();
        $this->approvedLocalFare($vehicle);
        $this->innova(['name' => 'Shift', 'slug' => 'shift']); // active, no fare

        // Local: the vehicle is listed with its rate, selected, and the select is enabled.
        $html = $this->get(route('home'))->assertOk()->getContent();
        $select = str($html)->between('<select id="home-vehicle"', '</select>')->toString();
        $this->assertStringNotContainsString(' disabled ', str($select)->before('>')->toString());
        $this->assertMatchesRegularExpression('#<option value="'.$vehicle->id.'" data-vehicle-option[^>]*selected[^>]*>Innova crysta — ₹16/km</option>#', $select);
        $this->assertMatchesRegularExpression('#data-name="Shift"\s+disabled\s+hidden#', $select);
        $this->assertStringContainsString('>Choose a vehicle</option>', $select);
        $this->assertMatchesRegularExpression('#data-vehicle-empty role="status"\s+hidden#', $html);
    }

    public function test_homepage_vehicle_dropdown_explains_when_no_vehicle_is_available(): void
    {
        $vehicle = $this->innova();
        $this->approvedLocalFare($vehicle); // Local only — nothing for Rental

        $html = $this->get(route('home', ['type' => 'rental']))->assertOk()->getContent();
        $select = str($html)->between('<select id="home-vehicle"', '</select>')->toString();

        $this->assertMatchesRegularExpression('#aria-describedby="home-vehicle-empty"\s+disabled#', $select);
        $this->assertStringContainsString('>No vehicles available for Rental</option>', $select);
        $this->assertDoesNotMatchRegularExpression('#data-vehicle-empty role="status"\s+hidden#', $html);
        $this->assertStringContainsString('No vehicles are available for <span data-vehicle-empty-type>Rental</span> online booking right now.', $html);

        // The server still refuses a Rental booking for this vehicle.
        $this->post(route('booking.store'), [
            'booking_type' => 'rental', 'vehicle_id' => $vehicle->id,
            'pickup' => 'White Town, Puducherry', 'travel_date' => now('Asia/Kolkata')->addDays(2)->toDateString(),
            'pickup_time' => '10:00', 'passengers' => 2, 'name' => 'Test Customer', 'phone' => '9876543210',
        ])->assertSessionHasErrors('vehicle_id');
    }

    public function test_inactive_vehicles_stay_hidden(): void
    {
        $vehicle = $this->innova(['is_active' => false]);
        $this->approvedLocalFare($vehicle);

        $this->get(route('home'))->assertOk()->assertDontSee('Innova crysta');
        $this->get(route('services'))->assertOk()->assertDontSee('Innova crysta');
        $this->get(route('booking.create'))->assertOk()->assertDontSee('Innova crysta');
    }

    public function test_the_admin_active_toggle_controls_visibility(): void
    {
        $vehicle = $this->innova();
        $this->get(route('home'))->assertSee('Innova crysta');

        $this->actingAs($this->admin())->patch(route('admin.vehicles.toggle', $vehicle))->assertRedirect();
        $this->assertFalse($vehicle->fresh()->is_active);

        auth()->logout();
        $this->get(route('home'))->assertDontSee('Innova crysta');
    }
}
