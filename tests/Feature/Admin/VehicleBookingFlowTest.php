<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingType;
use App\Models\BookingRequest;
use App\Models\FareRule;
use App\Models\User;
use App\Models\Vehicle;
use App\Places\GoogleRouteDistance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Admin → Vehicles → Fares → booking form → estimate → booking request,
 * starting from an empty database (no seeded vehicles).
 */
class VehicleBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    /** A real 1×1 PNG, so image validation runs without the GD extension. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-10 10:00', 'Asia/Kolkata'));
        config([
            'site.contact.whatsapp' => '+91 98765 43210',
            'services.google_places.key' => null,
            'services.google_maps.browser_key' => null,
            'services.google_routes.key' => 'test-routes-key',
        ]);
        Http::fake([GoogleRouteDistance::ENDPOINT => Http::response(['routes' => [['distanceMeters' => 20_000]]])]);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function png(string $name = 'innova.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    /** @return array<string, mixed> */
    private function vehicleInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Innova Crysta',
            'category' => 'muv',
            'description' => 'Spacious 7-seater for families.',
            'passenger_capacity' => '7',
            'luggage_capacity' => '4',
            'sort_order' => '5',
            'is_active' => '1',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function fareInput(array $overrides = []): array
    {
        return array_merge([
            'rate_per_km' => '18',
            'min_billable_km' => '10',
            'minimum_fare' => '250',
            'driver_allowance_per_day' => '',
            'tolls_parking' => 'extra',
            'is_active' => '1',
            'is_approved' => '0',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function booking(Vehicle $vehicle, array $overrides = []): array
    {
        return array_merge([
            'form_token' => (string) Str::uuid(),
            'booking_type' => 'local',
            'vehicle_id' => $vehicle->id,
            'pickup' => 'Puducherry Railway Station',
            'pickup_place_id' => 'ChIJPickupPlace0001',
            'dropoff' => 'Auroville Visitors Centre',
            'dropoff_place_id' => 'ChIJDropoffPlace001',
            'travel_date' => '2026-10-12',
            'pickup_time' => '09:30',
            'passengers' => 6,
            'name' => 'Arun Kumar',
            'phone' => '9876512345',
        ], $overrides);
    }

    private function vehicleOptions(string $html): array
    {
        preg_match("/data-vehicle-options='([^']+)'/", $html, $match);

        return json_decode(html_entity_decode($match[1] ?? '{}'), true);
    }

    // --- Admin vehicle management -----------------------------------------------------

    public function test_admin_can_create_a_vehicle_with_all_fields_and_an_image(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.vehicles.store'), [...$this->vehicleInput(), 'image' => $this->png()])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.fares.index'))
            ->assertSessionHas('status', 'Innova Crysta was added. Configure its fares below.');

        $vehicle = Vehicle::sole();
        $this->assertSame('innova-crysta', $vehicle->slug);
        $this->assertSame('muv', $vehicle->category);
        $this->assertSame('MUV / MPV', $vehicle->categoryLabel());
        $this->assertSame(7, $vehicle->passenger_capacity);
        $this->assertSame(4, $vehicle->luggage_capacity);
        $this->assertSame(5, $vehicle->sort_order);
        $this->assertTrue($vehicle->is_active);
        $this->assertStringStartsWith('vehicles/', $vehicle->image_path);
        Storage::disk('public')->assertExists($vehicle->image_path);

        $this->get(route('admin.vehicles.index'))
            ->assertOk()
            ->assertSee(['Innova Crysta', 'MUV / MPV', 'No fare'])
            ->assertSee('storage/'.$vehicle->image_path, false);
    }

    public function test_vehicle_categories_include_hatchback_to_tempo_traveller(): void
    {
        $this->actingAs($this->admin())->get(route('admin.vehicles.create'))
            ->assertOk()
            ->assertSee(['Hatchback', 'Sedan', 'SUV', 'MUV / MPV', 'Tempo Traveller / Van']);
    }

    public function test_vehicle_input_and_image_are_validated(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.vehicles.store'), [])->assertSessionHasErrors(['name', 'category']);
        $this->post(route('admin.vehicles.store'), $this->vehicleInput([
            'category' => 'spaceship',
            'passenger_capacity' => '0',
            'luggage_capacity' => '-1',
            'sort_order' => 'first',
        ]))->assertSessionHasErrors(['category', 'passenger_capacity', 'luggage_capacity', 'sort_order']);

        // Not an image (a real upload, so the type is detected from the content,
        // not the ".png" name), a disallowed type, and too large.
        $fakePng = tempnam(sys_get_temp_dir(), 'h2c');
        file_put_contents($fakePng, '<?php echo "not an image";');
        $this->post(route('admin.vehicles.store'), $this->vehicleInput(['image' => new UploadedFile($fakePng, 'car.png', 'image/png', null, true)]))
            ->assertSessionHasErrors('image');
        @unlink($fakePng);
        $this->post(route('admin.vehicles.store'), $this->vehicleInput(['image' => UploadedFile::fake()->create('car.svg', 10, 'image/svg+xml')]))
            ->assertSessionHasErrors('image');
        $this->post(route('admin.vehicles.store'), $this->vehicleInput(['image' => $this->png()->size(3000)]))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Vehicle::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_admin_can_edit_a_vehicle(): void
    {
        $this->actingAs($this->admin());
        $vehicle = Vehicle::factory()->create(['name' => 'Etios', 'slug' => 'etios', 'category' => 'sedan', 'passenger_capacity' => 4]);

        $this->get(route('admin.vehicles.edit', $vehicle))->assertOk()->assertSee('value="Etios"', false);

        $this->put(route('admin.vehicles.update', $vehicle), $this->vehicleInput(['name' => 'Etios Liva', 'slug' => 'etios', 'category' => 'hatchback', 'passenger_capacity' => '4', 'luggage_capacity' => '1', 'sort_order' => '2']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.vehicles.edit', $vehicle))
            ->assertSessionHas('status', 'Changes saved.');

        $vehicle->refresh();
        $this->assertSame('Etios Liva', $vehicle->name);
        $this->assertSame('hatchback', $vehicle->category);
        $this->assertSame(1, $vehicle->luggage_capacity);
        $this->assertSame(2, $vehicle->sort_order);
    }

    public function test_admin_can_activate_and_deactivate_a_vehicle(): void
    {
        $this->actingAs($this->admin());
        $vehicle = Vehicle::factory()->create(['name' => 'Ertiga', 'is_active' => true]);

        $this->from(route('admin.vehicles.index'))->patch(route('admin.vehicles.toggle', $vehicle))
            ->assertRedirect(route('admin.vehicles.index'))
            ->assertSessionHas('status', 'Ertiga is inactive and hidden from customers.');
        $this->assertFalse($vehicle->fresh()->is_active);

        $this->patch(route('admin.vehicles.toggle', $vehicle));
        $this->assertTrue($vehicle->fresh()->is_active);
    }

    public function test_admin_can_view_vehicle_details_and_why_it_is_not_bookable(): void
    {
        $this->actingAs($this->admin());
        $vehicle = Vehicle::factory()->create(['name' => 'Ertiga', 'category' => 'muv', 'passenger_capacity' => 6, 'luggage_capacity' => 3, 'description' => 'Comfortable <b>MPV</b>']);
        FareRule::factory()->for($vehicle)->create(['booking_type' => 'local', 'rate_per_km' => '15', 'is_approved' => true]);
        FareRule::factory()->for($vehicle)->create(['booking_type' => 'outstation_one_way', 'rate_per_km' => '15', 'is_approved' => false]);
        FareRule::factory()->for($vehicle)->create(['booking_type' => 'rental', 'rate_per_km' => null, 'is_approved' => true]);

        $this->get(route('admin.vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee(['Ertiga', 'MUV / MPV', '6', '3 bags', 'Booking availability'])
            ->assertSee('Comfortable &lt;b&gt;MPV&lt;/b&gt;', false)
            ->assertSee(['₹15/km', 'Bookable'])          // Local
            ->assertSee('Not approved yet')              // One way
            ->assertSee('No active rental package')      // Rental
            ->assertSee('No fare');                      // Round trip
    }

    public function test_guests_and_non_admins_cannot_manage_vehicles(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->get(route('admin.vehicles.show', $vehicle))->assertRedirect(route('admin.login'));
        $this->patch(route('admin.vehicles.toggle', $vehicle))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.vehicles.index'))->assertForbidden();
        $this->get(route('admin.vehicles.show', $vehicle))->assertForbidden();
        $this->patch(route('admin.vehicles.toggle', $vehicle))->assertForbidden();
        $this->post(route('admin.vehicles.store'), $this->vehicleInput())->assertForbidden();

        $this->assertTrue($vehicle->fresh()->is_active);
        $this->assertSame(1, Vehicle::count());
    }

    // --- End to end -------------------------------------------------------------------

    public function test_vehicle_added_in_admin_appears_on_the_booking_form_only_once_its_fare_is_approved_and_can_be_booked(): void
    {
        $admin = $this->admin();

        // 1. Empty database: the form says so instead of inventing vehicles.
        $this->get(route('home'))->assertOk()->assertSee('No vehicles are available for');

        // 2. Admin adds the vehicle (with an image) — no fare yet, so not offered.
        $this->actingAs($admin)->post(route('admin.vehicles.store'), [...$this->vehicleInput(), 'image' => $this->png()])->assertSessionHasNoErrors();
        $vehicle = Vehicle::sole();
        auth()->logout();
        $this->assertFalse($this->vehicleOptions($this->get(route('home'))->getContent())[$vehicle->id]['local']['available']);

        // 3. Admin adds a Local fare as a proposal — still not offered.
        $this->actingAs($admin)->post(route('admin.fares.store'), $this->fareInput(['vehicle_id' => $vehicle->id, 'booking_type' => 'local']))->assertSessionHasNoErrors();
        $fare = FareRule::sole();
        auth()->logout();
        $this->get(route('home'))->assertDontSee('₹18/km');
        $this->post(route('booking.store'), $this->booking($vehicle))->assertSessionHasErrors('vehicle_id');

        // 4. Admin approves it — the vehicle card appears on the homepage and booking page.
        $this->actingAs($admin)->put(route('admin.fares.update', $fare), $this->fareInput(['is_approved' => '1']))->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.show', $vehicle))->assertSee('Bookable');
        auth()->logout();

        // Booking page: a vehicle card with image, capacities and rate.
        // Homepage: the compact form's Vehicle Type option, plus the "Our vehicles" card below it.
        foreach ([route('home') => '7 passengers', route('booking.create') => '7 seats'] as $url => $seats) {
            $html = $this->get($url)->assertOk()
                ->assertSee(['Innova Crysta', 'MUV / MPV', $seats, '4 bags', '₹18/km'])
                ->assertSee('storage/'.$vehicle->image_path, false)
                ->getContent();
            $this->assertMatchesRegularExpression('/data-vehicle-option data-vehicle-id="'.$vehicle->id.'"[\s>]/', $html);
            $options = $this->vehicleOptions($html);
            $this->assertTrue($options[$vehicle->id]['local']['available']);
            $this->assertFalse($options[$vehicle->id]['outstation_one_way']['available'], 'No One Way fare configured.');
        }

        // 5. Selecting the vehicle gives a server-calculated estimate: 20 km × ₹18.
        $this->postJson(route('booking.estimate'), $this->booking($vehicle))
            ->assertOk()
            ->assertJsonPath('estimate.status', 'estimated')
            ->assertJsonPath('estimate.total', '₹360')
            ->assertJsonPath('distance', '20 km (approx. by road)');
        // ...and none for a trip type the vehicle is not offered for.
        $this->postJson(route('booking.estimate'), $this->booking($vehicle, ['booking_type' => 'outstation_one_way']))->assertStatus(422);

        // 6. The booking request stores the vehicle and trip; a posted price is ignored.
        $this->post(route('booking.store'), $this->booking($vehicle, ['fare_amount' => '1']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('booking.confirmation'));

        $booking = BookingRequest::sole();
        $this->assertSame($vehicle->id, $booking->vehicle_id);
        $this->assertSame('Innova Crysta', $booking->vehicle_name);
        $this->assertSame('local', $booking->booking_type->value);
        $this->assertSame('Puducherry Railway Station', $booking->pickup);
        $this->assertSame('Auroville Visitors Centre', $booking->dropoff);
        $this->assertSame('2026-10-12', $booking->travel_date->toDateString());
        $this->assertSame(6, $booking->passengers);
        $this->assertSame(20_000, $booking->distance_meters);
        $this->assertSame('360.00', $booking->fare_amount);
        $this->assertSame(BookingRequest::STATUS_NEW, $booking->status);

        // 7. It is listed under Admin → Booking requests.
        $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()->assertSee([$booking->reference, 'Innova Crysta', 'Arun Kumar']);
        $this->get(route('admin.bookings.show', $booking))->assertOk()->assertSee('Innova Crysta');

        // 8. Deactivating the vehicle hides it again and blocks new bookings.
        $this->patch(route('admin.vehicles.toggle', $vehicle));
        auth()->logout();
        $this->assertDoesNotMatchRegularExpression('/data-vehicle-option data-vehicle-id="'.$vehicle->id.'"\s*>/', $this->get(route('home'))->getContent());
        $this->post(route('booking.store'), $this->booking($vehicle))->assertSessionHasErrors('vehicle_id');
    }

    public function test_new_vehicle_appears_in_the_homepage_vehicle_dropdown_for_each_trip_type_with_an_approved_fare(): void
    {
        $admin = $this->admin();
        // An existing vehicle that is only offered for Local.
        $sedan = Vehicle::factory()->create(['name' => 'Sedan', 'slug' => 'sedan', 'sort_order' => 1]);
        FareRule::factory()->for($sedan)->create(['booking_type' => 'local', 'rate_per_km' => '14', 'is_approved' => true]);

        $dropdown = function (string $type): string {
            $html = $this->get(route('home', ['type' => $type]))->assertOk()->getContent();

            return str($html)->between('<select id="home-vehicle"', '</select>')->toString();
        };
        $offered = fn (string $select, Vehicle $vehicle, string $label) => (bool) preg_match(
            '#<option value="'.$vehicle->id.'" data-vehicle-option data-vehicle-id="'.$vehicle->id.'" data-name="'.preg_quote($vehicle->name, '#').'"\s*(selected\s*)?>'.preg_quote($label, '#').'</option>#', $select);
        $hidden = fn (string $select, Vehicle $vehicle) => (bool) preg_match(
            '#data-vehicle-id="'.$vehicle->id.'" data-name="[^"]*"\s+disabled\s+hidden\s*>#', $select);

        // 1. Admin adds Innova (no image needed). The admin explains it is not bookable yet.
        $this->actingAs($admin)->post(route('admin.vehicles.store'), $this->vehicleInput(['name' => 'Innova', 'sort_order' => '2']))
            ->assertRedirect(route('admin.fares.index'))
            ->assertSessionHas('status', 'Innova was added. Configure its fares below.');
        $innova = Vehicle::where('name', 'Innova')->sole();
        $this->get(route('admin.vehicles.index'))->assertSee('Not in the website booking form yet.')->assertSee('Configure its fare in Fares &amp; Pricing', false);
        $this->get(route('admin.vehicles.show', $innova))->assertSee('Not in the website booking form yet.');
        auth()->logout();

        // Listed in "Our vehicles", but not selectable in the booking dropdown.
        $this->get(route('home'))->assertSee('Enquire about Innova');
        $this->assertTrue($hidden($dropdown('local'), $innova));
        $this->assertTrue($offered($dropdown('local'), $sedan, 'Sedan — ₹14/km'));

        // 2. A proposed (unapproved) Local fare still keeps it out of the dropdown.
        $this->actingAs($admin)->post(route('admin.fares.store'), $this->fareInput(['vehicle_id' => $innova->id, 'booking_type' => 'local', 'rate_per_km' => '18']))
            ->assertSessionHasNoErrors();
        $localFare = FareRule::where('vehicle_id', $innova->id)->sole();
        auth()->logout();
        $this->assertTrue($hidden($dropdown('local'), $innova));

        // 3. Admin approves Local and adds an approved Outstation One Way fare.
        $this->actingAs($admin)->put(route('admin.fares.update', $localFare), $this->fareInput(['rate_per_km' => '18', 'is_approved' => '1']))->assertSessionHasNoErrors();
        $this->post(route('admin.fares.store'), $this->fareInput(['vehicle_id' => $innova->id, 'booking_type' => 'outstation_one_way', 'rate_per_km' => '16', 'is_approved' => '1']))
            ->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.index'))->assertDontSee('Not in the website booking form yet.');
        auth()->logout();

        // 4. The dropdown now offers each vehicle with its own approved rate for the trip type.
        $local = $dropdown('local');
        $this->assertTrue($offered($local, $sedan, 'Sedan — ₹14/km'));
        $this->assertTrue($offered($local, $innova, 'Innova — ₹18/km'));

        $oneWay = $dropdown('outstation_one_way');
        $this->assertTrue($offered($oneWay, $innova, 'Innova — ₹16/km'));
        $this->assertTrue($hidden($oneWay, $sedan), 'Sedan has no Outstation fare.');

        $rental = $dropdown('rental');
        $this->assertTrue($hidden($rental, $innova));
        $this->assertTrue($hidden($rental, $sedan));
        $this->assertStringContainsString('>No vehicles available for Rental</option>', $rental);

        // 5. The same per-type data drives switching in the browser (booking.js).
        $options = $this->vehicleOptions($this->get(route('home'))->getContent());
        $this->assertSame(['available' => true, 'rate' => '₹18/km'], array_intersect_key($options[$innova->id]['local'], ['available' => 1, 'rate' => 1]));
        $this->assertSame(['available' => true, 'rate' => '₹16/km'], array_intersect_key($options[$innova->id]['outstation_one_way'], ['available' => 1, 'rate' => 1]));
        $this->assertFalse($options[$innova->id]['outstation_round_trip']['available']);
    }

    public function test_hatchback_added_activated_and_given_an_approved_local_fare_can_be_booked_for_local(): void
    {
        $admin = $this->admin();

        // 1–2. Add a Hatchback in Admin (saved inactive), then activate it.
        $this->actingAs($admin)->post(route('admin.vehicles.store'), $this->vehicleInput([
            'name' => 'Hatchback', 'category' => 'hatchback', 'passenger_capacity' => '4', 'luggage_capacity' => '1', 'is_active' => '0', 'image' => $this->png('hatchback.png'),
        ]))->assertSessionHasNoErrors();
        $hatchback = Vehicle::where('slug', 'hatchback')->sole();
        $this->assertFalse($hatchback->is_active);
        $this->patch(route('admin.vehicles.toggle', $hatchback))->assertSessionHas('status');
        $this->assertTrue($hatchback->fresh()->is_active);

        // 3. Configure its Local fare (saved as a proposal: not offered yet).
        $this->get(route('admin.fares.create', ['vehicle' => $hatchback->id, 'type' => 'local']))->assertOk();
        $this->post(route('admin.fares.store'), $this->fareInput(['vehicle_id' => $hatchback->id, 'booking_type' => 'local', 'rate_per_km' => '11', 'min_billable_km' => '5', 'minimum_fare' => '']))
            ->assertSessionHasNoErrors();
        $fare = FareRule::sole();
        $this->assertSame('Not approved yet', $fare->fresh()->bookingIssue());
        $this->assertFalse(Vehicle::query()->bookableFor(BookingType::Local)->exists());

        // 4. Approve it.
        $this->put(route('admin.fares.update', $fare), $this->fareInput(['rate_per_km' => '11', 'min_billable_km' => '5', 'minimum_fare' => '', 'is_approved' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertTrue(Vehicle::query()->bookableFor(BookingType::Local)->whereKey($hatchback->id)->exists());
        auth()->logout();

        // 5–6. Homepage, Local: the Hatchback card is shown with image and capacities.
        // (The compact homepage form lists it in Vehicle Type; "Our vehicles" shows the card.)
        $html = $this->get(route('home'))->assertOk()
            ->assertSee(['Hatchback', '4 passengers', '1 bags', '₹11/km'])
            ->assertSee('Hatchback — ₹11/km')
            ->assertSee('storage/'.$hatchback->fresh()->image_path, false)
            ->getContent();
        $this->assertMatchesRegularExpression('/value="local" class="sr-only" data-type-input data-label="Local" aria-label="Local" checked/', $html);
        $this->assertMatchesRegularExpression('/data-vehicle-option data-vehicle-id="'.$hatchback->id.'"[\s>]/', $html);
        $this->assertTrue($this->vehicleOptions($html)[$hatchback->id]['local']['available']);

        // 7. Selecting it gives a server-side estimate: 20 km × ₹11 = ₹220.
        $this->postJson(route('booking.estimate'), $this->booking($hatchback, ['passengers' => 3]))
            ->assertOk()
            ->assertJsonPath('estimate.status', 'estimated')
            ->assertJsonPath('estimate.total', '₹220');

        // 8. Submit a booking; it appears under Admin → Booking requests.
        $this->post(route('booking.store'), $this->booking($hatchback, ['passengers' => 3]))->assertRedirect(route('booking.confirmation'));
        $booking = BookingRequest::sole();
        $this->assertSame($hatchback->id, $booking->vehicle_id);
        $this->assertSame('220.00', $booking->fare_amount);
        $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()->assertSee([$booking->reference, 'Hatchback']);
    }

    public function test_rental_vehicle_needs_an_approved_fare_with_an_active_package(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create(['name' => 'Dzire', 'category' => 'sedan', 'passenger_capacity' => 4]);

        $this->actingAs($admin)->post(route('admin.fares.store'), $this->fareInput(['vehicle_id' => $vehicle->id, 'booking_type' => 'rental', 'rate_per_km' => '14', 'is_approved' => '1']))
            ->assertSessionHasNoErrors();
        $fare = FareRule::sole();
        $this->assertSame('No active rental package', $fare->fresh()->bookingIssue());
        $this->assertFalse($this->vehicleOptions($this->get(route('booking.create'))->getContent())[$vehicle->id]['rental']['available']);

        $this->post(route('admin.fares.packages.store', $fare), ['name' => '8 hours', 'hours' => '8', 'included_km' => '80', 'price' => '2000', 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $package = $fare->packages()->sole();
        auth()->logout();

        $html = $this->get(route('booking.create', ['type' => 'rental']))->assertSee('8 hours (8 hrs / 80 km) — ₹2,000')->getContent();
        $this->assertTrue($this->vehicleOptions($html)[$vehicle->id]['rental']['available']);

        $rental = ['booking_type' => 'rental', 'dropoff' => '', 'dropoff_place_id' => '', 'passengers' => 2];
        $this->postJson(route('booking.estimate'), $this->booking($vehicle, [...$rental, 'rental_package_id' => $package->id]))
            ->assertOk()->assertJsonPath('estimate.total', '₹2,000');
        $this->post(route('booking.store'), $this->booking($vehicle, $rental))->assertSessionHasErrors('rental_package_id');
        $this->post(route('booking.store'), $this->booking($vehicle, [...$rental, 'rental_package_id' => $package->id]))->assertSessionHasNoErrors();

        $booking = BookingRequest::sole();
        $this->assertSame($package->id, $booking->rental_package_id);
        $this->assertSame('8 hours (8 hrs / 80 km) — ₹2,000', $booking->rental_package_label);
        $this->assertSame('2000.00', $booking->fare_amount);
    }

    public function test_invalid_or_unofferable_vehicle_ids_cannot_be_submitted(): void
    {
        $vehicle = Vehicle::factory()->create();
        FareRule::factory()->for($vehicle)->create(['booking_type' => 'local', 'rate_per_km' => '15', 'is_approved' => true]);

        foreach ([999999, 'abc', '', null, [1]] as $id) {
            $this->post(route('booking.store'), [...$this->booking($vehicle), 'vehicle_id' => $id])->assertSessionHasErrors('vehicle_id');
        }
        $this->postJson(route('booking.estimate'), [...$this->booking($vehicle), 'vehicle_id' => 999999])->assertStatus(422);

        // A round trip with no configured return billing is incomplete and not offered.
        FareRule::factory()->for($vehicle)->create(['booking_type' => 'outstation_round_trip', 'rate_per_km' => '15', 'is_approved' => true, 'round_trip_distance' => null]);
        $this->post(route('booking.store'), $this->booking($vehicle, ['booking_type' => 'outstation_round_trip', 'return_date' => '2026-10-13']))->assertSessionHasErrors('vehicle_id');

        $this->assertSame(0, BookingRequest::count());
    }

    public function test_booking_form_cards_are_responsive_and_accessible(): void
    {
        $vehicle = Vehicle::factory()->create(['name' => 'Ertiga', 'category' => 'muv']);
        FareRule::factory()->for($vehicle)->create(['booking_type' => 'local', 'rate_per_km' => '15', 'is_approved' => true]);

        $html = $this->get(route('booking.create'))->assertOk()->getContent();

        // One column on phones, two from the small breakpoint.
        $this->assertStringContainsString('data-vehicle-list', $html);
        $this->assertMatchesRegularExpression('/class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-2" data-vehicle-list/', $html);
        // The compact homepage form uses a labelled Vehicle Type select instead of cards.
        $home = $this->get(route('home'))->getContent();
        $this->assertMatchesRegularExpression('/<label for="home-vehicle" class="form-label">Vehicle Type/', $home);
        $this->assertMatchesRegularExpression('/<select id="home-vehicle" name="vehicle_id" required class="form-control[^"]*" data-vehicle-select/', $home);
        $this->assertStringContainsString('data-name="Ertiga"', $home);
        // Each card is a labelled radio button, so it can be selected by touch, mouse or keyboard.
        $this->assertStringContainsString('type="radio" name="vehicle_id" value="'.$vehicle->id.'" class="sr-only" data-vehicle-input data-name="Ertiga"', $html);
        $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1">', $html);
    }
}
