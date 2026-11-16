<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingType;
use App\Models\FareRule;
use App\Models\Member;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\VehicleFareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleFareManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VehicleFareSeeder::class);
    }

    private function sedanFare(string $type): FareRule
    {
        return Vehicle::where('slug', 'sedan')->first()->fareRules()->where('booking_type', $type)->first();
    }

    private function fareInput(array $overrides = []): array
    {
        return array_merge([
            'rate_per_km' => '13',
            'min_billable_km' => '13',
            'tolls_parking' => 'extra',
            'is_active' => '1',
            'is_approved' => '0',
        ], $overrides);
    }

    public function test_non_admins_cannot_manage_vehicles_or_fares(): void
    {
        $fare = $this->sedanFare('local');
        $vehicle = $fare->vehicle;
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.vehicles.store'), ['name' => 'Hatchback', 'category' => 'hatchback'])->assertForbidden();
        $this->put(route('admin.vehicles.update', $vehicle), ['name' => 'X', 'category' => 'sedan'])->assertForbidden();
        $this->delete(route('admin.vehicles.destroy', $vehicle))->assertForbidden();
        $this->put(route('admin.fares.update', $fare), $this->fareInput(['rate_per_km' => '1']))->assertForbidden();
        $this->patch(route('admin.fares.toggle', $fare))->assertForbidden();

        $this->assertSame('13.00', $fare->fresh()->rate_per_km);
        $this->assertSame(4, Vehicle::count());
    }

    public function test_admin_can_add_a_new_vehicle_category_and_its_fare(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.vehicles.store'), ['name' => 'Hatchback', 'category' => 'hatchback', 'is_active' => '1'])
            ->assertRedirect(route('admin.fares.index'));
        $vehicle = Vehicle::where('slug', 'hatchback')->first();
        $this->assertNull($vehicle->passenger_capacity, 'Capacity stays unset unless entered.');

        $this->get(route('admin.fares.create', ['vehicle' => $vehicle->id, 'type' => 'local']))->assertOk();
        $this->post(route('admin.fares.store'), $this->fareInput(['vehicle_id' => $vehicle->id, 'booking_type' => 'local', 'rate_per_km' => '12.50']))
            ->assertRedirect();

        $this->assertSame('12.50', $vehicle->fareRules()->first()->rate_per_km);

        // One fare per vehicle and booking type.
        $this->post(route('admin.fares.store'), $this->fareInput(['vehicle_id' => $vehicle->id, 'booking_type' => 'local']))
            ->assertSessionHasErrors('booking_type');
    }

    public function test_admin_can_change_sedan_and_suv_rates_without_code_changes(): void
    {
        $this->actingAs($this->admin());
        $suv = Vehicle::where('slug', 'suv')->first()->fareRules()->where('booking_type', 'outstation_one_way')->first();

        $this->put(route('admin.fares.update', $this->sedanFare('outstation_one_way')), $this->fareInput(['rate_per_km' => '13.50', 'is_approved' => '1']))
            ->assertRedirect();
        $this->put(route('admin.fares.update', $suv), $this->fareInput(['rate_per_km' => '15', 'min_billable_km' => '20']))
            ->assertRedirect();

        $this->assertSame('13.50', $this->sedanFare('outstation_one_way')->rate_per_km);
        $this->assertTrue($this->sedanFare('outstation_one_way')->is_approved);
        $this->assertSame('15.00', $suv->fresh()->rate_per_km);
        $this->assertSame('20.0', $suv->fresh()->min_billable_km);
        // Other booking types are independent.
        $this->assertSame('13.00', $this->sedanFare('local')->rate_per_km);
    }

    public function test_fare_values_are_validated(): void
    {
        $this->actingAs($this->admin());
        $fare = $this->sedanFare('outstation_one_way');

        $this->put(route('admin.fares.update', $fare), $this->fareInput([
            'rate_per_km' => '-1',
            'min_billable_km' => '13.25',
            'minimum_fare' => '100.555',
            'driver_allowance_per_day' => 'abc',
            'extra_charge' => '50',
            'tolls_parking' => 'sometimes',
        ]))->assertSessionHasErrors(['rate_per_km', 'min_billable_km', 'minimum_fare', 'driver_allowance_per_day', 'extra_charge_label', 'tolls_parking']);

        $this->put(route('admin.fares.update', $fare), $this->fareInput(['rate_per_km' => '']))->assertSessionHasErrors('rate_per_km');
        $this->assertSame('13.00', $fare->fresh()->rate_per_km);
    }

    public function test_round_trip_fare_requires_a_return_policy_and_others_ignore_it(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.fares.update', $this->sedanFare('outstation_round_trip')), $this->fareInput(['round_trip_distance' => '']))
            ->assertSessionHasErrors('round_trip_distance');
        $this->put(route('admin.fares.update', $this->sedanFare('outstation_round_trip')), $this->fareInput(['round_trip_distance' => 'one_way_only']))
            ->assertSessionHasNoErrors();
        $this->assertSame('one_way_only', $this->sedanFare('outstation_round_trip')->round_trip_distance);

        $this->put(route('admin.fares.update', $this->sedanFare('local')), $this->fareInput(['round_trip_distance' => 'both_ways']))
            ->assertSessionHasNoErrors();
        $this->assertNull($this->sedanFare('local')->round_trip_distance);
    }

    public function test_vehicle_and_booking_type_of_a_fare_cannot_be_changed(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.fares.update', $this->sedanFare('local')), $this->fareInput(['booking_type' => 'outstation_one_way']))
            ->assertSessionHasErrors('booking_type');
    }

    public function test_fares_can_be_deactivated_which_hides_the_vehicle_for_that_type(): void
    {
        $this->actingAs($this->admin());
        FareRule::query()->update(['is_approved' => true]);
        $fare = $this->sedanFare('outstation_one_way');

        $this->patch(route('admin.fares.toggle', $fare))->assertRedirect();
        $this->assertFalse($fare->fresh()->is_active);

        $sedan = Vehicle::where('slug', 'sedan')->first();
        $this->assertFalse(Vehicle::query()->bookableFor(BookingType::OutstationOneWay)->whereKey($sedan->id)->exists());
        $this->assertTrue(Vehicle::query()->bookableFor(BookingType::Local)->whereKey($sedan->id)->exists());
    }

    public function test_fare_preview_shows_sample_calculations(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.fares.edit', ['fare' => $this->sedanFare('outstation_one_way'), 'sample_km' => '42.5']))
            ->assertOk()
            ->assertSee('Fare preview')
            ->assertSee('₹169')        // 5 km → minimum 13 km × ₹13
            ->assertSee('₹552.50')     // 42.5 km × ₹13
            // Amounts are an admin preview; customers see none while the fare is unapproved.
            ->assertSee('Admin preview only — customers see no amount until this fare is approved.')
            ->assertSee('Fare will be confirmed by H2 CABS.')
            ->assertDontSee('From ₹169');
    }

    public function test_round_trip_preview_uses_days(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.fares.edit', ['fare' => $this->sedanFare('outstation_round_trip'), 'sample_km' => '100', 'days' => 2]))
            ->assertOk()
            ->assertSee('₹2,600'); // 100 km × 2 ways × ₹13
    }

    public function test_deleting_a_vehicle_removes_its_fares(): void
    {
        $vehicle = Vehicle::where('slug', 'premium')->first();

        $this->actingAs($this->admin())->delete(route('admin.vehicles.destroy', $vehicle))->assertRedirect();

        $this->assertNull(Vehicle::find($vehicle->id));
        $this->assertSame(0, FareRule::where('vehicle_id', $vehicle->id)->count());
        $this->get(route('services'))->assertOk();
    }

    public function test_admin_manages_rental_packages_separately_from_local_fares(): void
    {
        $this->actingAs($this->admin());
        $rental = $this->sedanFare('rental');

        // A rental rule needs no per-km rate; its rate is an optional extra-km rate.
        $this->put(route('admin.fares.update', $rental), $this->fareInput(['rate_per_km' => '', 'min_billable_km' => '', 'extra_hour_rate' => '150']))
            ->assertSessionHasNoErrors();
        $this->assertNull($rental->fresh()->rate_per_km);
        $this->assertSame('150.00', $rental->fresh()->extra_hour_rate);

        $this->post(route('admin.fares.packages.store', $rental), ['name' => '4 hours', 'hours' => '4', 'included_km' => '40', 'price' => '1200', 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $package = $rental->packages()->first();
        $this->assertSame('1200.00', $package->price);

        $this->put(route('admin.fares.packages.update', [$rental, $package]), ['name' => '4 hours', 'hours' => '4', 'price' => '1300', 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('1300.00', $package->fresh()->price);

        $this->get(route('admin.fares.edit', $rental))->assertOk()->assertSee('Rental packages')->assertSee('₹1,300');

        // Local pricing is untouched by rental changes.
        $this->assertSame('13.00', $this->sedanFare('local')->rate_per_km);

        $this->delete(route('admin.fares.packages.destroy', [$rental, $package]))->assertRedirect();
        $this->assertSame(0, $rental->packages()->count());
    }

    public function test_rental_package_values_are_validated_and_scoped(): void
    {
        $this->actingAs($this->admin());
        $rental = $this->sedanFare('rental');

        $this->post(route('admin.fares.packages.store', $rental), ['name' => '', 'hours' => '0', 'price' => '-5'])
            ->assertSessionHasErrors(['name', 'hours', 'price']);

        // Packages only exist on rental fares.
        $this->post(route('admin.fares.packages.store', $this->sedanFare('local')), ['name' => 'X', 'hours' => '4', 'price' => '100'])
            ->assertNotFound();

        // A package can only be edited through its own fare.
        $package = $rental->packages()->create(['name' => 'Half day', 'hours' => '4', 'price' => '900']);
        $otherRental = Vehicle::where('slug', 'suv')->first()->fareRules()->where('booking_type', 'rental')->first();
        $this->put(route('admin.fares.packages.update', [$otherRental, $package]), ['name' => 'Y', 'hours' => '4', 'price' => '1'])
            ->assertNotFound();
        $this->assertSame('900.00', $package->fresh()->price);
    }

    public function test_guests_and_non_admins_cannot_manage_rental_packages(): void
    {
        $rental = $this->sedanFare('rental');
        $package = $rental->packages()->create(['name' => '4 hours', 'hours' => '4', 'price' => '1200']);
        $input = ['name' => 'Cheap', 'hours' => '4', 'price' => '1'];

        $this->post(route('admin.fares.packages.store', $rental), $input)->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create());
        $this->post(route('admin.fares.packages.store', $rental), $input)->assertForbidden();
        $this->put(route('admin.fares.packages.update', [$rental, $package]), $input)->assertForbidden();
        $this->delete(route('admin.fares.packages.destroy', [$rental, $package]))->assertForbidden();

        $this->assertSame(1, $rental->packages()->count());
        $this->assertSame('1200.00', $package->fresh()->price);
    }

    public function test_package_display_order_and_no_km_limit_are_saved(): void
    {
        $this->actingAs($this->admin());
        $rental = $this->sedanFare('rental');

        $this->post(route('admin.fares.packages.store', $rental), ['name' => 'Full day', 'hours' => '8', 'included_km' => '', 'price' => '2400', 'sort_order' => '5', 'is_active' => '1'])
            ->assertSessionHasNoErrors();

        $package = $rental->packages()->first();
        $this->assertNull($package->included_km);
        $this->assertSame(5, $package->sort_order);
        $this->assertSame('Full day (8 hrs) — ₹2,400', $package->summary());
    }

    public function test_admin_edits_and_deactivates_a_vehicle_and_it_leaves_the_booking_form(): void
    {
        $this->actingAs($this->admin());
        $suv = Vehicle::where('slug', 'suv')->first();

        $this->put(route('admin.vehicles.update', $suv), ['name' => 'SUV (7 seater)', 'category' => 'suv', 'passenger_capacity' => '7', 'luggage_capacity' => '', 'is_active' => '1'])
            ->assertRedirect(route('admin.vehicles.edit', $suv));
        $this->assertSame('SUV (7 seater)', $suv->fresh()->name);
        $this->assertSame(7, $suv->fresh()->passenger_capacity);
        $this->assertNull($suv->fresh()->luggage_capacity);
        $this->get(route('booking.create'))->assertSee('SUV (7 seater)');

        $this->put(route('admin.vehicles.update', $suv), ['name' => 'SUV (7 seater)', 'category' => 'suv', 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($suv->fresh()->is_active);
        $this->get(route('booking.create'))->assertDontSee('SUV (7 seater)');
        $this->assertSame(4, $suv->fareRules()->count(), 'Deactivating keeps the fares.');
    }

    public function test_vehicle_image_upload_is_validated_and_replaced(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $sedan = Vehicle::where('slug', 'sedan')->first();
        $base = ['name' => 'Sedan', 'category' => 'sedan', 'is_active' => '1'];

        $this->put(route('admin.vehicles.update', $sedan), [...$base, 'image' => UploadedFile::fake()->create('car.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('image');

        $this->put(route('admin.vehicles.update', $sedan), [...$base, 'image' => UploadedFile::fake()->image('car.jpg', 800, 500)]);
        $first = $sedan->fresh()->image_path;
        Storage::disk('public')->assertExists($first);

        $this->put(route('admin.vehicles.update', $sedan), [...$base, 'image' => UploadedFile::fake()->image('car2.webp', 800, 500)]);
        Storage::disk('public')->assertMissing($first);
        $this->assertStringStartsWith('vehicles/', $sedan->fresh()->image_path);
    }

    public function test_vehicle_slug_must_be_unique(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.vehicles.store'), ['name' => 'Sedan', 'category' => 'sedan', 'is_active' => '1'])
            ->assertSessionHasErrors('slug');
    }

    public function test_fare_approval_is_explicit_and_reversible_and_shown_publicly(): void
    {
        $this->actingAs($this->admin());
        $fare = $this->sedanFare('outstation_one_way');
        $this->assertFalse($fare->is_approved);

        $this->put(route('admin.fares.update', $fare), $this->fareInput(['is_approved' => '1']))->assertSessionHasNoErrors();
        $this->assertTrue($fare->fresh()->is_approved);
        $this->postJson(route('booking.estimate'), ['booking_type' => 'outstation_one_way', 'vehicle_id' => $fare->vehicle_id])
            ->assertJsonPath('estimate.headline', 'Fare will be confirmed after the route is verified.')
            ->assertJsonPath('estimate.details.0', 'Rate: ₹13 per km');
        $this->get(route('services'))->assertSee('₹13/km');

        // Saving the form without the box unticks approval (no silent carry-over).
        $this->put(route('admin.fares.update', $fare), $this->fareInput())->assertSessionHasNoErrors();
        $this->assertFalse($fare->fresh()->is_approved);
        $this->assertFalse($this->sedanFare('local')->is_approved, 'Approving one fare never approves others.');
        // Withdrawn approval: the vehicle is no longer offered for this type.
        $this->postJson(route('booking.estimate'), ['booking_type' => 'outstation_one_way', 'vehicle_id' => $fare->vehicle_id])
            ->assertStatus(422)
            ->assertJsonMissingPath('estimate');
        $this->get(route('services'))->assertDontSee('₹13/km');
    }

    public function test_dashboard_shows_real_counts_and_setup_status(): void
    {
        Member::factory()->count(2)->create();
        Member::factory()->unpublished()->create();
        config(['site.contact.whatsapp' => null, 'services.google_maps.browser_key' => null]);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Members', '3', '2 shown on website'])
            ->assertSee('8 still proposed') // Sedan + SUV × local, rental, one way, round trip
            ->assertSee(['WhatsApp number', 'Settings or H2CABS_WHATSAPP'])
            ->assertSee('Not configured');
    }

    public function test_distance_based_fares_still_require_a_rate(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.fares.update', $this->sedanFare('local')), $this->fareInput(['rate_per_km' => '']))
            ->assertSessionHasErrors('rate_per_km');
    }
}
