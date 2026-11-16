<?php

namespace Tests\Feature;

use App\Booking\BookingSummary;
use App\Content\DestinationCatalog;
use App\Fares\FareEstimate;
use App\Models\BookingRequest;
use App\Models\FareRule;
use App\Models\RentalPackage;
use App\Models\Vehicle;
use App\Places\GoogleRouteDistance;
use Database\Seeders\VehicleFareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_ID = 'ChIJPickupPlace0001';

    private const DROPOFF_ID = 'ChIJDropoffPlace001';

    private Vehicle $sedan;

    private Vehicle $suv;

    protected function setUp(): void
    {
        parent::setUp();

        // 10:00 IST on a fixed day keeps date/time validation deterministic.
        Carbon::setTestNow(Carbon::parse('2026-10-10 10:00', 'Asia/Kolkata'));
        config([
            'site.contact.whatsapp' => '+91 98765 43210',
            'services.google_places.key' => null,
            'services.google_routes.key' => null,
            'services.google_maps.browser_key' => null,
        ]);

        $this->seed(VehicleFareSeeder::class);
        // Customers can only book approved fares, so the baseline is an
        // association that has approved the seeded rates. Tests about
        // unapproved fares withdraw approval explicitly.
        $this->approveFares();
        $this->sedan = Vehicle::where('slug', 'sedan')->first();
        $this->suv = Vehicle::where('slug', 'suv')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function booking(array $overrides = []): array
    {
        return array_merge([
            'form_token' => (string) Str::uuid(),
            'booking_type' => 'outstation_one_way',
            'vehicle_id' => $this->sedan->id,
            'pickup' => 'Hotel Atithi, Puducherry',
            'pickup_place_id' => self::PICKUP_ID,
            'dropoff' => 'Chennai Central',
            'dropoff_place_id' => self::DROPOFF_ID,
            'travel_date' => '2026-10-12',
            'pickup_time' => '09:30',
            'passengers' => 3,
            'name' => 'Priya Raman',
            'phone' => '9876512345',
            'notes' => 'Two suitcases & a child seat',
        ], $overrides);
    }

    /** The seeded (proposed) rates are unapproved until an admin approves them. */
    private function approveFares(): void
    {
        FareRule::query()->update(['is_approved' => true]);
    }

    /** A rental is offered only with an active package on an approved rental fare. */
    private function rentalPackage(Vehicle $vehicle, array $attributes = []): RentalPackage
    {
        return $vehicle->fareRules()->where('booking_type', 'rental')->firstOrFail()->packages()
            ->create([...['name' => '4 hours', 'hours' => '4', 'included_km' => '40', 'price' => '1200'], ...$attributes]);
    }

    private function fakeRoute(int $meters): void
    {
        config(['services.google_routes.key' => 'test-routes-key']);
        Http::fake([GoogleRouteDistance::ENDPOINT => Http::response(['routes' => [['distanceMeters' => $meters]]])]);
    }

    private function latest(): BookingRequest
    {
        return BookingRequest::query()->latest('id')->firstOrFail();
    }

    /** The WhatsApp message for the most recently stored request. */
    private function message(): string
    {
        return BookingSummary::make($this->latest(), app(DestinationCatalog::class))->toWhatsAppMessage();
    }

    private function whatsappUrl(string $html): string
    {
        preg_match('#href="(https://wa\.me/[^"]+)"[^>]*data-whatsapp-link#', $html, $match);

        return html_entity_decode($match[1] ?? '');
    }

    // --- Booking page --------------------------------------------------------

    public function test_booking_page_offers_four_independent_booking_types(): void
    {
        $this->get(route('booking.create'))
            ->assertOk()
            ->assertSeeInOrder(['Local', 'Rental', 'Outstation One Way', 'Outstation Round Trip'])
            ->assertSee('value="local"', false)
            ->assertSee('value="rental"', false)
            ->assertSee('value="outstation_one_way"', false)
            ->assertSee('value="outstation_round_trip"', false)
            ->assertDontSee('local_rental')
            ->assertDontSee('Local Rental');
    }

    public function test_vehicles_with_only_unapproved_fares_are_not_offered(): void
    {
        FareRule::query()->update(['is_approved' => false]);

        $html = $this->get(route('booking.create'))
            ->assertOk()
            ->assertDontSee('₹13/km')
            ->assertDontSee('₹14/km')
            ->assertDontSee('Fare confirmed on booking')
            ->assertDontSee('Premium')
            ->assertSee('No vehicles are available for')
            ->assertSee('name="return_date"', false)
            ->assertSee('name="return_time"', false)
            ->assertSee('data-estimate-url="'.route('booking.estimate').'"', false)
            ->getContent();

        // The cards are rendered hidden and disabled; none is selectable.
        $this->assertDoesNotMatchRegularExpression('/data-vehicle-option data-vehicle-id="\d+"\s*>/', $html);
        $this->assertStringNotContainsString('"available":true', html_entity_decode($html));
    }

    public function test_approved_rates_are_shown_on_the_booking_page(): void
    {
        $this->get(route('booking.create'))
            ->assertSee(['₹13/km', '₹14/km'])
            ->assertDontSee('Fare confirmed on booking')
            ->assertDontSee('name="rental_duration"', false);
    }

    public function test_only_vehicles_offered_for_the_selected_trip_type_are_shown(): void
    {
        // The SUV's local fare is withdrawn; it stays offered for outstation.
        $this->suv->fareRules()->where('booking_type', 'local')->update(['is_approved' => false]);

        $local = $this->get(route('booking.create', ['type' => 'local']))->getContent();
        $this->assertMatchesRegularExpression('/data-vehicle-option data-vehicle-id="'.$this->sedan->id.'"\s*>/', $local, 'Sedan is shown for Local.');
        $this->assertMatchesRegularExpression('/data-vehicle-option data-vehicle-id="'.$this->suv->id.'"\s+hidden/', $local, 'SUV is hidden for Local.');

        $oneWay = $this->get(route('booking.create', ['type' => 'outstation_one_way']))->getContent();
        $this->assertMatchesRegularExpression('/data-vehicle-option data-vehicle-id="'.$this->suv->id.'"\s*>/', $oneWay, 'SUV is shown for One Way.');

        // The per-type availability map the form uses when the trip type changes.
        preg_match("/data-vehicle-options='([^']+)'/", $local, $match);
        $options = json_decode(html_entity_decode($match[1]), true);
        $this->assertTrue($options[$this->sedan->id]['local']['available']);
        $this->assertFalse($options[$this->suv->id]['local']['available']);
        $this->assertTrue($options[$this->suv->id]['outstation_one_way']['available']);
        $this->assertSame('₹14/km', $options[$this->suv->id]['outstation_one_way']['rate']);
        $this->assertFalse($options[$this->sedan->id]['rental']['available'], 'No rental packages yet, so no rentals.');

        $this->rentalPackage($this->sedan);
        $this->get(route('booking.create', ['type' => 'rental']))
            ->assertSee('4 hours (4 hrs / 40 km) — ₹1,200')
            ->assertSee('From ₹1,200');
    }

    public function test_links_preselect_type_vehicle_and_destinations(): void
    {
        $this->get(route('booking.create', ['type' => 'outstation_round_trip', 'vehicle' => 'suv']))
            ->assertSee('value="outstation_round_trip" class="sr-only" data-type-input data-label="Outstation Round Trip" checked', false)
            ->assertSee('value="'.$this->suv->id.'" class="sr-only" data-vehicle-input data-name="SUV"', false);

        $this->get(route('booking.create', ['type' => 'rental']))
            ->assertSee('value="rental" class="sr-only" data-type-input data-label="Rental" checked', false);

        // Destination day plans are rentals.
        $this->get(route('booking.create', ['destination' => 'auroville,white-town']))
            ->assertSee('value="rental" class="sr-only" data-type-input data-label="Rental" checked', false)
            ->assertSee('value="auroville" class="size-4 accent-brand-green-600" checked', false);

        // Old links still land on a sensible type.
        $this->get(route('booking.create', ['type' => 'local_rental']))
            ->assertSee('value="local" class="sr-only" data-type-input data-label="Local" checked', false);
        $this->get(route('booking.create', ['trip' => 'round_trip']))
            ->assertSee('value="outstation_round_trip" class="sr-only" data-type-input data-label="Outstation Round Trip" checked', false);
    }

    public function test_selected_vehicle_is_preselected_and_unavailable_vehicles_show_a_message(): void
    {
        $this->get(route('booking.create', ['vehicle' => 'suv']))
            ->assertSee('value="'.$this->suv->id.'" class="sr-only" data-vehicle-input data-name="SUV"'."\n                        checked", false)
            ->assertDontSee('not available at the moment');

        $this->get(route('booking.create', ['vehicle' => 'premium']))
            ->assertSee('The vehicle you selected is not available at the moment.');

        $this->get(route('booking.create', ['vehicle' => 'does-not-exist']))
            ->assertSee('The vehicle you selected is not available at the moment.');
    }

    public function test_query_string_values_are_escaped(): void
    {
        $this->get(route('booking.create', ['pickup' => '"><script>alert(1)</script>']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&quot;&gt;&lt;script&gt;', false);
    }

    // --- Mobile number -----------------------------------------------------------

    public function test_mobile_number_field_is_a_numeric_ten_digit_input(): void
    {
        $this->get(route('booking.create'))
            ->assertSee('name="phone"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('maxlength="10"', false)
            ->assertSee('pattern="[0-9]{10}"', false)
            ->assertSee('10 digits, numbers only — without +91 or 0.');
    }

    public function test_mobile_number_must_be_exactly_ten_digits(): void
    {
        $message = 'Please enter a 10-digit mobile number, using digits only (no +91 or 0 prefix).';

        foreach (['987654321', '98765123456', '+919876512345', '09876512345', '98765-12345', 'abcdefghij', '98765l2345'] as $phone) {
            $this->post(route('booking.store'), $this->booking(['phone' => $phone]))
                ->assertSessionHasErrors(['phone' => $message]);
        }

        $this->post(route('booking.store'), $this->booking(['phone' => '']))->assertSessionHasErrors('phone');
        $this->assertSame(0, BookingRequest::count());
    }

    public function test_mobile_number_is_stored_as_entered_without_a_country_code(): void
    {
        $this->post(route('booking.store'), $this->booking(['phone' => '9876512345']))->assertSessionHasNoErrors();
        $this->assertSame('9876512345', $this->latest()->customer_phone);

        // Spaces typed or pasted inside the number are not significant.
        $this->post(route('booking.store'), $this->booking(['phone' => ' 98765 12346 ']))->assertSessionHasNoErrors();
        $this->assertSame('9876512346', $this->latest()->customer_phone);
        $this->assertStringContainsString("Mobile: 9876512346\n", $this->message());
    }

    // --- Google locations ----------------------------------------------------------

    public function test_locations_must_be_chosen_from_google_suggestions(): void
    {
        $this->post(route('booking.store'), $this->booking(['pickup_place_id' => '']))
            ->assertSessionHasErrors(['pickup_place_id' => 'Please choose the pickup location from the Google suggestions.']);

        $this->post(route('booking.store'), $this->booking(['dropoff_place_id' => '']))
            ->assertSessionHasErrors(['dropoff_place_id' => 'Please choose the destination from the Google suggestions.']);

        $this->post(route('booking.store'), $this->booking(['pickup_place_id' => '<bad id>']))
            ->assertSessionHasErrors('pickup_place_id');

        // A rental may end where it started: no drop-off, so no drop-off place.
        $package = $this->rentalPackage($this->sedan);
        $this->post(route('booking.store'), $this->booking(['booking_type' => 'rental', 'rental_package_id' => $package->id, 'dropoff' => '', 'dropoff_place_id' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, BookingRequest::count());
    }

    // --- Local -----------------------------------------------------------------

    public function test_local_requires_a_drop_location(): void
    {
        $this->post(route('booking.store'), $this->booking(['booking_type' => 'local', 'dropoff' => '']))
            ->assertSessionHasErrors('dropoff');
    }

    public function test_local_uses_the_local_fare_not_the_outstation_fare(): void
    {
        $this->approveFares();
        $this->fakeRoute(20_000);
        $this->sedan->fareRules()->where('booking_type', 'local')->update(['rate_per_km' => '20.00']);

        $this->post(route('booking.store'), $this->booking(['booking_type' => 'local', 'dropoff' => 'Lawspet']))->assertSessionHasNoErrors();

        $message = $this->message();
        $this->assertStringContainsString('Booking type: Local', $message);
        $this->assertStringContainsString('Drop-off: Lawspet', $message);
        $this->assertStringContainsString('Fare: ₹400 (estimated fare — not a confirmed quote)', $message); // 20 km × ₹20, not ₹13
        $this->assertStringNotContainsString('Rental', $message);
    }

    public function test_local_without_a_configured_rate_is_not_bookable(): void
    {
        $this->sedan->fareRules()->where('booking_type', 'local')->update(['rate_per_km' => null]);

        $this->post(route('booking.store'), $this->booking(['booking_type' => 'local', 'dropoff' => 'Lawspet']))->assertSessionHasErrors('vehicle_id');
        $this->assertSame(0, BookingRequest::count());
    }

    // --- Rental ----------------------------------------------------------------

    public function test_rental_without_packages_is_not_offered(): void
    {
        $rental = ['booking_type' => 'rental', 'dropoff' => '', 'rental_duration' => 'half_day'];

        $this->post(route('booking.store'), $this->booking($rental))->assertSessionHasErrors('vehicle_id');
        $this->postJson(route('booking.estimate'), $this->booking($rental))->assertStatus(422);

        // An inactive package does not count.
        $this->rentalPackage($this->sedan, ['is_active' => false]);
        $this->post(route('booking.store'), $this->booking($rental))->assertSessionHasErrors('vehicle_id');
        $this->assertSame(0, BookingRequest::count());
    }

    public function test_rental_is_priced_by_its_package_not_by_distance(): void
    {
        $package = $this->rentalPackage($this->sedan);
        $this->fakeRoute(50_000); // Distance must not price a rental.

        $this->post(route('booking.store'), $this->booking([
            'booking_type' => 'rental',
            'rental_package_id' => $package->id,
            'dropoff' => '',
            'sightseeing' => ['auroville', 'white-town'],
        ]))->assertSessionHasNoErrors();

        $message = $this->message();
        $this->assertStringContainsString('Booking type: Rental', $message);
        $this->assertStringContainsString('Places to visit: Auroville, French Quarter (White Town)', $message);
        $this->assertStringContainsString('Fare: ₹1,200 (estimated fare — not a confirmed quote)', $message);
        $this->assertStringNotContainsString('₹13', $message, 'Rental must not inherit the local/outstation per-km rate.');
        $this->assertStringNotContainsString('Estimated distance', $message);
    }

    public function test_rental_with_packages_requires_one_of_the_vehicles_packages(): void
    {
        $rule = $this->sedan->fareRules()->where('booking_type', 'rental')->first();
        $rule->update(['rate_per_km' => '15', 'extra_hour_rate' => '150']);
        $package = $this->rentalPackage($this->sedan);
        $suvPackage = $this->rentalPackage($this->suv, ['name' => 'SUV 4 hours', 'included_km' => null, 'price' => '1500']);

        $rental = ['booking_type' => 'rental', 'dropoff' => ''];

        $this->post(route('booking.store'), $this->booking([...$rental, 'rental_duration' => 'half_day']))
            ->assertSessionHasErrors(['rental_package_id' => 'Please choose a rental package.']);
        $this->post(route('booking.store'), $this->booking([...$rental, 'rental_package_id' => $suvPackage->id]))
            ->assertSessionHasErrors('rental_package_id');

        // Unapproved: the vehicle is not offered for rentals at all.
        $rule->update(['is_approved' => false]);
        $this->post(route('booking.store'), $this->booking([...$rental, 'rental_package_id' => $package->id]))->assertSessionHasErrors('vehicle_id');
        $this->assertSame(0, BookingRequest::count());

        // Approved: the package price is the estimate.
        $rule->update(['is_approved' => true]);
        $this->post(route('booking.store'), $this->booking([...$rental, 'rental_package_id' => $package->id]))->assertSessionHasNoErrors();
        $message = $this->message();
        $this->assertStringContainsString('Rental package: 4 hours (4 hrs / 40 km) — ₹1,200', $message);
        $this->assertStringContainsString('Fare: ₹1,200 (estimated fare — not a confirmed quote)', $message);

        $this->get(route('booking.confirmation'))
            ->assertSee('Beyond 40 km: ₹15 per km.')
            ->assertSee('Beyond 4 hours: ₹150 per hour.');
    }

    public function test_rental_packages_are_listed_only_when_approved_and_active(): void
    {
        $rule = $this->sedan->fareRules()->where('booking_type', 'rental')->first();
        $rule->update(['is_approved' => false]);
        $this->rentalPackage($this->sedan, ['name' => 'Full day', 'hours' => '8', 'included_km' => '80', 'price' => '2400']);
        $this->rentalPackage($this->sedan, ['name' => 'Hidden', 'hours' => '2', 'included_km' => null, 'price' => '600', 'is_active' => false]);

        $this->get(route('booking.create', ['type' => 'rental']))
            ->assertSee('name="rental_package_id"', false)
            ->assertDontSee('Full day (8 hrs')
            ->assertDontSee('₹2,400')
            ->assertSee('No vehicles are available for');

        $rule->update(['is_approved' => true]);

        $this->get(route('booking.create', ['type' => 'rental']))
            ->assertSee('Full day (8 hrs / 80 km) — ₹2,400')
            ->assertSee('From ₹2,400')
            ->assertDontSee('Hidden (2 hrs');
    }

    // --- Outstation ------------------------------------------------------------

    public function test_outstation_one_way_with_distance_builds_an_encoded_whatsapp_message(): void
    {
        $this->approveFares();
        $this->fakeRoute(152_300);

        $this->post(route('booking.store'), $this->booking())->assertRedirect(route('booking.confirmation'));
        $reference = $this->latest()->reference;

        $html = $this->get(route('booking.confirmation'))
            ->assertOk()
            ->assertSee($reference)
            ->assertSee('has <strong>not been sent yet</strong>', false)
            ->assertSee('Tolls and parking charges, if any, are paid separately.')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->getContent();

        $url = $this->whatsappUrl($html);
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $url, 'Sent to the single configured business number.');
        $this->assertStringNotContainsString(' ', $url);
        $this->assertStringContainsString('%0A', $url, 'Line breaks are encoded.');
        $this->assertStringContainsString('%20%26%20', $url, 'An ampersand cannot cut the message short.');
        $this->assertStringContainsString('%E2%82%B91%2C979.90', $url, 'Rupee sign and comma are percent-encoded.');

        $message = rawurldecode(Str::after($url, '?text='));
        $this->assertSame($this->message(), $message, 'The link carries exactly the message built from the stored request.');

        foreach ([
            "Booking reference: {$reference}\n",
            "Booking type: Outstation One Way\n",
            "Vehicle: Sedan\n",
            'Pickup: Hotel Atithi, Puducherry (https://www.google.com/maps/place/?q=place_id:'.self::PICKUP_ID.")\n",
            'Destination: Chennai Central (https://www.google.com/maps/place/?q=place_id:'.self::DROPOFF_ID.")\n",
            "Departure date: Mon, 12 Oct 2026\n",
            "Pickup time: 9:30 AM\n",
            "Estimated distance: 152.3 km (approx. by road)\n",
            "Fare: ₹1,979.90 (estimated fare — not a confirmed quote)\n",
            "Passengers: 3\n",
            "Name: Priya Raman\n",
            "Mobile: 9876512345\n",
            'Notes: Two suitcases & a child seat',
            'This is a booking request. Please confirm availability and the final fare.',
        ] as $line) {
            $this->assertStringContainsString($line, $message);
        }

        // Google Routes is asked with the chosen place IDs.
        Http::assertSent(fn (Request $request) => $request->hasHeader('X-Goog-Api-Key', 'test-routes-key')
            && $request->hasHeader('X-Goog-FieldMask', 'routes.distanceMeters')
            && $request['origin'] === ['placeId' => self::PICKUP_ID]
            && $request['destination'] === ['placeId' => self::DROPOFF_ID]);
    }

    public function test_outstation_requires_a_destination(): void
    {
        $this->post(route('booking.store'), $this->booking(['dropoff' => '']))->assertSessionHasErrors('dropoff');
    }

    public function test_suv_uses_its_own_rate(): void
    {
        $this->approveFares();
        $this->fakeRoute(100_000);

        $this->post(route('booking.store'), $this->booking(['vehicle_id' => $this->suv->id]));

        $this->assertStringContainsString('Fare: ₹1,400 (estimated fare — not a confirmed quote)', $this->message());
    }

    public function test_round_trip_has_its_own_policy_and_return_details(): void
    {
        $this->approveFares();
        $this->fakeRoute(100_000);

        $this->post(route('booking.store'), $this->booking([
            'booking_type' => 'outstation_round_trip',
            'return_date' => '2026-10-14',
            'return_time' => '18:00',
        ]))->assertSessionHasNoErrors();

        $message = $this->message();
        $this->assertStringContainsString('Return: Wed, 14 Oct 2026, 6:00 PM', $message);
        $this->assertStringContainsString('Estimated distance: 100 km (approx. one way by road)', $message);
        $this->assertStringContainsString('Fare: ₹2,600 (estimated', $message); // 200 km × ₹13 ("both ways" policy)

        // Switching the round-trip policy changes only round trips.
        $this->sedan->fareRules()->where('booking_type', 'outstation_round_trip')->update(['round_trip_distance' => 'one_way_only']);
        $this->post(route('booking.store'), $this->booking(['booking_type' => 'outstation_round_trip', 'return_date' => '2026-10-14']));
        $this->assertStringContainsString('Fare: ₹1,300 (estimated', $this->message());
    }

    public function test_round_trip_return_must_not_be_before_departure(): void
    {
        $roundTrip = ['booking_type' => 'outstation_round_trip'];

        $this->post(route('booking.store'), $this->booking($roundTrip))->assertSessionHasErrors('return_date');
        $this->post(route('booking.store'), $this->booking([...$roundTrip, 'return_date' => '2026-10-11']))
            ->assertSessionHasErrors(['return_date' => 'The return date cannot be earlier than the departure date.']);
        $this->post(route('booking.store'), $this->booking([...$roundTrip, 'return_date' => '2026-12-31']))
            ->assertSessionHasErrors('return_date');
        // Same-day return must be after the departure time.
        $this->post(route('booking.store'), $this->booking([...$roundTrip, 'return_date' => '2026-10-12', 'return_time' => '08:00']))
            ->assertSessionHasErrors(['return_time' => 'The return time must be later than the departure time.']);
        $this->post(route('booking.store'), $this->booking([...$roundTrip, 'return_date' => '2026-10-12', 'return_time' => '20:00']))
            ->assertSessionHasNoErrors();
    }

    public function test_short_trip_is_billed_at_the_minimum_distance(): void
    {
        $this->approveFares();
        $this->fakeRoute(4_000);

        $this->post(route('booking.store'), $this->booking());

        $this->assertStringContainsString('Fare: ₹169 (estimated', $this->message());
    }

    // --- Fare display rules ---------------------------------------------------------

    public function test_unapproved_fares_are_not_bookable_and_never_show_an_amount(): void
    {
        $this->fakeRoute(100_000);
        $this->sedan->fareRules()->where('booking_type', 'outstation_one_way')->update(['is_approved' => false]);

        $api = $this->postJson(route('booking.estimate'), $this->booking())
            ->assertStatus(422)
            ->assertJson(['message' => 'This vehicle is not available for Outstation One Way. Please choose another.'])
            ->json();
        $this->assertStringNotContainsString('₹', json_encode($api, JSON_UNESCAPED_UNICODE));

        $this->post(route('booking.store'), $this->booking())
            ->assertSessionHasErrors(['vehicle_id' => 'Please choose one of the available vehicles for this trip.']);
        $this->assertSame(0, BookingRequest::count());

        // The proposed rate is not published on the booking page either.
        $this->get(route('booking.create', ['type' => 'outstation_one_way']))->assertDontSee('₹13/km');
    }

    public function test_approved_fare_without_a_distance_waits_for_route_verification(): void
    {
        $this->post(route('booking.store'), $this->booking())->assertSessionHasNoErrors();

        $this->assertStringNotContainsString('Estimated distance', $this->message());
        $this->assertStringContainsString('Fare: Fare will be confirmed after the route is verified', $this->message());
        $this->assertStringNotContainsString('From ₹', $this->message());
        $this->assertNull($this->latest()->fare_amount);

        $api = $this->postJson(route('booking.estimate'), $this->booking())->json();
        $this->assertSame('Fare will be confirmed after the route is verified.', $api['estimate']['headline']);
        $this->assertContains('Rate: ₹13 per km', $api['estimate']['details']);
        $this->assertContains('Minimum billable distance: 13 km', $api['estimate']['details']);
    }

    public function test_route_provider_failure_falls_back_to_route_verification(): void
    {
        $this->approveFares();
        config(['services.google_routes.key' => 'test-routes-key']);
        Http::fake([GoogleRouteDistance::ENDPOINT => Http::response(['error' => 'quota'], 429)]);

        $this->post(route('booking.store'), $this->booking())->assertSessionHasNoErrors();

        $this->assertStringContainsString('Fare will be confirmed after the route is verified', $this->message());
    }

    // --- Vehicles ----------------------------------------------------------------

    public function test_vehicle_must_be_active_and_offered_for_the_booking_type(): void
    {
        $premium = Vehicle::where('slug', 'premium')->first();

        $this->post(route('booking.store'), $this->booking(['vehicle_id' => $premium->id]))->assertSessionHasErrors('vehicle_id');
        $this->post(route('booking.store'), $this->booking(['vehicle_id' => 999]))->assertSessionHasErrors('vehicle_id');
        $this->post(route('booking.store'), $this->booking(['vehicle_id' => '']))->assertSessionHasErrors('vehicle_id');

        // Deactivating the sedan's Rental fare removes it from Rental only.
        $package = $this->rentalPackage($this->sedan);
        $this->post(route('booking.store'), $this->booking(['booking_type' => 'rental', 'rental_package_id' => $package->id, 'dropoff' => '']))->assertSessionHasNoErrors();
        $this->sedan->fareRules()->where('booking_type', 'rental')->update(['is_active' => false]);
        $this->post(route('booking.store'), $this->booking(['booking_type' => 'rental', 'rental_package_id' => $package->id, 'dropoff' => '']))->assertSessionHasErrors('vehicle_id');
        $this->post(route('booking.store'), $this->booking(['booking_type' => 'local', 'dropoff' => 'Lawspet']))->assertSessionHasNoErrors();

        // Deactivating the vehicle removes it from every booking type.
        $this->sedan->update(['is_active' => false]);
        $this->post(route('booking.store'), $this->booking(['booking_type' => 'local', 'dropoff' => 'Lawspet']))->assertSessionHasErrors('vehicle_id');
        $this->postJson(route('booking.estimate'), $this->booking(['booking_type' => 'local']))->assertStatus(422);

        // A fare that starts in the future is not yet bookable.
        $this->suv->fareRules()->where('booking_type', 'outstation_one_way')->update(['effective_from' => '2026-11-01']);
        $this->post(route('booking.store'), $this->booking(['vehicle_id' => $this->suv->id]))->assertSessionHasErrors('vehicle_id');
    }

    public function test_passengers_cannot_exceed_vehicle_capacity(): void
    {
        $this->post(route('booking.store'), $this->booking(['passengers' => 5]))
            ->assertSessionHasErrors(['passengers' => 'This vehicle seats up to 4 passengers. Please choose a larger vehicle.']);

        $this->post(route('booking.store'), $this->booking(['vehicle_id' => $this->suv->id, 'passengers' => 5]))->assertSessionHasNoErrors();
    }

    public function test_fare_starting_today_applies_from_midnight_india_time(): void
    {
        // 02:00 IST on 12 Oct is still 11 Oct in UTC (the app timezone).
        Carbon::setTestNow(Carbon::parse('2026-10-12 02:00', 'Asia/Kolkata'));
        $this->approveFares();
        $this->fakeRoute(100_000);
        $this->sedan->fareRules()->where('booking_type', 'outstation_one_way')->update(['effective_from' => '2026-10-12', 'rate_per_km' => '20.00']);

        $this->post(route('booking.store'), $this->booking(['travel_date' => '2026-10-12', 'pickup_time' => '09:30']))
            ->assertSessionHasNoErrors();

        $this->assertStringContainsString('Fare: ₹2,000 (estimated', $this->message());
        $this->assertTrue($this->sedan->fresh()->fareRules()->where('booking_type', 'outstation_one_way')->first()->isInEffect());
    }

    public function test_estimate_api_whatsapp_message_and_review_page_agree_for_every_booking_type(): void
    {
        $this->approveFares();
        $this->fakeRoute(100_000);
        $rental = $this->sedan->fareRules()->where('booking_type', 'rental')->first();
        $package = $rental->packages()->create(['name' => '8 hours', 'hours' => '8', 'included_km' => '80', 'price' => '2400']);
        $this->sedan->fareRules()->where('booking_type', 'outstation_round_trip')
            ->update(['min_km_per_day' => '250', 'driver_allowance_per_day' => '300', 'extra_charge' => '100', 'extra_charge_label' => 'Permit']);

        $cases = [
            'local' => [['booking_type' => 'local', 'dropoff' => 'Lawspet'], '₹1,300'],
            'rental' => [['booking_type' => 'rental', 'dropoff' => '', 'rental_package_id' => $package->id], '₹2,400'],
            'one way' => [['booking_type' => 'outstation_one_way'], '₹1,300'],
            // 100 km × 2 = 200 km < 250 km × 3 days = 750 km → ₹9,750 + ₹300 × 3 + ₹100
            'round trip' => [['booking_type' => 'outstation_round_trip', 'return_date' => '2026-10-14'], '₹10,750'],
        ];

        foreach ($cases as $label => [$input, $expected]) {
            $booking = $this->booking($input);
            $api = $this->postJson(route('booking.estimate'), $booking)->assertOk()->json();

            $this->post(route('booking.store'), $booking)->assertSessionHasNoErrors();

            $this->assertSame(FareEstimate::ESTIMATED, $api['estimate']['status'], $label);
            $this->assertSame($expected, $api['estimate']['total'], $label);
            $this->assertSame("Estimated fare: {$expected} — not a confirmed quote", $api['estimate']['headline'], $label);
            $this->assertStringContainsString('Fare: '.$api['estimate']['summary'], $this->message(), $label);
            $this->assertSame(FareEstimate::ESTIMATED, $this->latest()->fare_status, $label);
            $this->get(route('booking.confirmation'))->assertSee($api['estimate']['summary']);
        }
    }

    public function test_homepage_widget_and_booking_page_share_one_form(): void
    {
        $extract = function (string $html): array {
            preg_match('#<form method="POST" action="([^"]+)"[^>]*data-booking-form.*?</form>#s', $html, $form);
            preg_match_all('#name="([a-z_\[\]]+)"#', $form[0], $names);
            preg_match_all('#name="booking_type" value="([a-z_]+)"#', $form[0], $types);
            // Vehicles are radio cards on /booking and a <select> on the compact homepage form.
            preg_match_all('#(?:name="vehicle_id" value="(\d+)"|<option value="(\d+)" data-vehicle-option)#', $form[0], $vehicles);

            // Same fields (in any order — the layouts differ), types and vehicles.
            $fields = array_values(array_unique($names[1]));
            sort($fields);

            return ['action' => $form[1], 'fields' => $fields, 'types' => $types[1], 'vehicles' => array_values(array_filter(array_merge($vehicles[1], $vehicles[2]), 'strlen'))];
        };

        $home = $extract($this->get(route('home'))->getContent());
        $page = $extract($this->get(route('booking.create'))->getContent());

        $this->assertSame(route('booking.store'), $home['action']);
        $this->assertSame($page, $home);
        $this->assertSame(['local', 'rental', 'outstation_one_way', 'outstation_round_trip'], $home['types']);
    }

    public function test_confirmation_never_claims_the_booking_is_confirmed(): void
    {
        $this->post(route('booking.store'), $this->booking());

        $this->get(route('booking.confirmation'))
            ->assertSee('not been sent yet', false)
            ->assertSee('This is a request, not a confirmed booking')
            ->assertDontSee('Booking confirmed')
            ->assertDontSee('booking is confirmed')
            ->assertDontSee('has been sent');

        $this->assertSame(BookingRequest::STATUS_NEW, $this->latest()->status);
    }

    // --- Storage -------------------------------------------------------------------

    public function test_every_booking_type_is_stored_with_a_reference(): void
    {
        $this->approveFares();
        $this->fakeRoute(100_000);
        $package = $this->rentalPackage($this->sedan);

        $cases = [
            'local' => ['booking_type' => 'local', 'dropoff' => 'Lawspet'],
            'rental' => ['booking_type' => 'rental', 'dropoff' => '', 'dropoff_place_id' => '', 'rental_package_id' => $package->id, 'sightseeing' => ['auroville']],
            'outstation_one_way' => ['booking_type' => 'outstation_one_way'],
            'outstation_round_trip' => ['booking_type' => 'outstation_round_trip', 'return_date' => '2026-10-14', 'return_time' => '18:00'],
        ];

        foreach ($cases as $type => $input) {
            $token = (string) Str::uuid();
            $this->post(route('booking.store'), $this->booking([...$input, 'form_token' => $token]))->assertRedirect(route('booking.confirmation'));

            $booking = BookingRequest::where('form_token', $token)->firstOrFail();
            $this->assertMatchesRegularExpression('/^H2C-261010-[2-9A-HJ-KM-NP-Z]{5}$/', $booking->reference, $type);
            $this->assertSame($type, $booking->booking_type->value);
            $this->assertSame(BookingRequest::STATUS_NEW, $booking->status);
            $this->assertSame($this->sedan->id, $booking->vehicle_id);
            $this->assertSame('Sedan', $booking->vehicle_name);
            $this->assertSame('Hotel Atithi, Puducherry', $booking->pickup);
            $this->assertSame(self::PICKUP_ID, $booking->pickup_place_id);
            $this->assertSame('2026-10-12', $booking->travel_date->toDateString());
            $this->assertSame('09:30', $booking->pickup_time);
            $this->assertSame(3, $booking->passengers);
            $this->assertSame('Priya Raman', $booking->customer_name);
            $this->assertSame('9876512345', $booking->customer_phone);
            $this->assertSame('Two suitcases & a child seat', $booking->notes);
            $this->assertSame(FareEstimate::ESTIMATED, $booking->fare_status);
            $this->assertNotNull($booking->fare_amount);
            $this->assertSame(BookingRequest::WHATSAPP_LINK_SHOWN, $booking->whatsapp_status);
            $this->assertNotNull($booking->created_at);
        }

        $rental = BookingRequest::where('booking_type', 'rental')->first();
        $this->assertSame($package->id, $rental->rental_package_id);
        $this->assertSame('4 hours (4 hrs / 40 km) — ₹1,200', $rental->rental_package_label);
        $this->assertSame(['auroville'], $rental->sightseeing);
        $this->assertNull($rental->dropoff);
        $this->assertNull($rental->distance_meters);
        $this->assertSame('1200.00', $rental->fare_amount);

        $oneWay = BookingRequest::where('booking_type', 'outstation_one_way')->first();
        $this->assertSame('Chennai Central', $oneWay->dropoff);
        $this->assertSame(self::DROPOFF_ID, $oneWay->dropoff_place_id);
        $this->assertSame(100_000, $oneWay->distance_meters);
        $this->assertSame('1300.00', $oneWay->fare_amount);
        $this->assertNull($oneWay->return_date);

        $roundTrip = BookingRequest::where('booking_type', 'outstation_round_trip')->first();
        $this->assertSame('2026-10-14', $roundTrip->return_date->toDateString());
        $this->assertSame('18:00', $roundTrip->return_time);
        $this->assertSame('2600.00', $roundTrip->fare_amount);

        $this->assertSame(4, BookingRequest::query()->distinct()->count('reference'));
    }

    public function test_tampered_fare_values_and_status_are_ignored(): void
    {
        $this->approveFares();
        $this->fakeRoute(100_000);

        $this->post(route('booking.store'), $this->booking([
            'fare' => '1', 'total' => '1', 'rate_per_km' => '0.01', 'distance_km' => '1', 'estimate' => '₹1',
            'fare_amount' => '1', 'fare_status' => 'estimated', 'status' => 'confirmed', 'reference' => 'H2C-HACKED',
            'whatsapp_status' => 'link_opened', 'distance_meters' => '1',
        ]))->assertSessionHasNoErrors();

        $booking = $this->latest();
        $this->assertSame('1300.00', $booking->fare_amount);
        $this->assertSame(100_000, $booking->distance_meters);
        $this->assertSame(BookingRequest::STATUS_NEW, $booking->status);
        $this->assertNotSame('H2C-HACKED', $booking->reference);
        $this->assertSame(BookingRequest::WHATSAPP_LINK_SHOWN, $booking->whatsapp_status);
        $this->assertStringContainsString('Fare: ₹1,300 (estimated', $this->message());
        $this->assertStringNotContainsString('0.01', $this->message());
    }

    public function test_client_cannot_book_an_unapproved_fare_by_posting_approval_or_a_price(): void
    {
        $this->fakeRoute(100_000);
        FareRule::query()->update(['is_approved' => false]);

        $this->post(route('booking.store'), $this->booking(['fare_amount' => '1300', 'fare_status' => 'estimated', 'is_approved' => 1]))
            ->assertSessionHasErrors('vehicle_id');

        $this->assertSame(0, BookingRequest::count());
        $this->assertFalse(FareRule::query()->where('is_approved', true)->exists());
    }

    public function test_estimate_endpoint_recalculates_on_the_server(): void
    {
        $this->approveFares();
        $this->fakeRoute(100_000);

        $this->postJson(route('booking.estimate'), [
            'booking_type' => 'outstation_one_way', 'vehicle_id' => $this->suv->id, 'pickup' => 'Puducherry', 'dropoff' => 'Chennai', 'total' => '1',
        ])->assertOk()->assertJson([
            'distance' => '100 km (approx. by road)',
            'estimate' => ['status' => 'estimated', 'total' => '₹1,400', 'headline' => 'Estimated fare: ₹1,400 — not a confirmed quote'],
        ]);

        // A rental is estimated only from one of the vehicle's packages.
        $package = $this->rentalPackage($this->sedan);
        $this->postJson(route('booking.estimate'), ['booking_type' => 'rental', 'vehicle_id' => $this->sedan->id])
            ->assertOk()->assertJson(['estimate' => ['total' => null, 'status' => 'requires_confirmation', 'reason' => 'no_package', 'headline' => 'Choose a rental package to see the estimate.']]);
        $this->postJson(route('booking.estimate'), ['booking_type' => 'rental', 'vehicle_id' => $this->sedan->id, 'rental_package_id' => $package->id])
            ->assertOk()->assertJson(['estimate' => ['total' => '₹1,200', 'status' => 'estimated']]);

        $this->postJson(route('booking.estimate'), ['booking_type' => 'outstation_one_way', 'vehicle_id' => Vehicle::where('slug', 'premium')->value('id')])
            ->assertStatus(422)->assertJson(['message' => 'This vehicle is not available for Outstation One Way. Please choose another.']);
        $this->postJson(route('booking.estimate'), ['booking_type' => 'teleport', 'vehicle_id' => $this->suv->id])->assertStatus(422);
    }

    public function test_required_and_invalid_fields_are_rejected(): void
    {
        $this->post(route('booking.store'), [])
            ->assertRedirect(route('booking.create'))
            ->assertSessionHasErrors(['booking_type', 'vehicle_id', 'pickup', 'pickup_place_id', 'travel_date', 'pickup_time', 'passengers', 'name', 'phone']);

        $this->post(route('booking.store'), $this->booking([
            'booking_type' => 'local_rental',
            'pickup' => 'ab',
            'travel_date' => '2026-02-30',
            'pickup_time' => '25:00',
            'passengers' => 40,
            'name' => 'R0bert <b>',
            'phone' => '12345',
            'notes' => str_repeat('a', 501),
        ]))->assertSessionHasErrors(['booking_type', 'pickup', 'travel_date', 'pickup_time', 'passengers', 'name', 'phone', 'notes']);

        $this->assertSame(0, BookingRequest::count());
    }

    public function test_past_dates_and_times_are_rejected(): void
    {
        $this->post(route('booking.store'), $this->booking(['travel_date' => '2026-10-09']))->assertSessionHasErrors('travel_date');
        $this->post(route('booking.store'), $this->booking(['travel_date' => '2026-10-10', 'pickup_time' => '09:00']))->assertSessionHasErrors('pickup_time');
        $this->post(route('booking.store'), $this->booking(['travel_date' => '2026-10-10', 'pickup_time' => '18:00']))->assertSessionHasNoErrors();
    }

    public function test_homepage_widget_errors_are_shown_on_the_booking_page_with_input_kept(): void
    {
        $this->from(route('home'))
            ->post(route('booking.store'), $this->booking(['phone' => 'abc']))
            ->assertRedirect(route('booking.create'))
            ->assertSessionHasErrors('phone');

        $this->get(route('booking.create'))
            ->assertSee('Please check the highlighted fields')
            ->assertSee('value="Priya Raman"', false)
            ->assertSee('value="Hotel Atithi, Puducherry"', false)
            ->assertSee('value="'.self::PICKUP_ID.'"', false);
    }

    public function test_background_estimate_requests_do_not_swallow_validation_errors(): void
    {
        // Regression: a debounced estimate request arriving between the failed
        // submission and the redirect used to consume the flashed errors.
        $this->from(route('home'))->post(route('booking.store'), $this->booking(['phone' => 'abc']));
        $this->postJson(route('booking.estimate'), ['booking_type' => 'local', 'vehicle_id' => $this->sedan->id])->assertOk();
        $this->getJson(route('places.search', ['q' => 'White Town']));

        $this->get(route('booking.create'))
            ->assertSee('Please check the highlighted fields')
            ->assertSee('value="Priya Raman"', false);
    }

    // --- WhatsApp ----------------------------------------------------------------

    public function test_missing_whatsapp_configuration_keeps_the_request(): void
    {
        config(['site.contact.whatsapp' => null]);

        $this->get(route('booking.create'))->assertSee('WhatsApp booking is being set up')->assertSee('Continue to booking review');
        $this->post(route('booking.store'), $this->booking())->assertRedirect(route('booking.confirmation'));

        $booking = $this->latest();
        $this->assertSame(BookingRequest::WHATSAPP_NOT_CONFIGURED, $booking->whatsapp_status);

        $this->get(route('booking.confirmation'))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('WhatsApp booking is not available at the moment.')
            ->assertSee('Your request has been saved.')
            ->assertSee('Priya Raman')
            ->assertDontSee('wa.me', false);
    }

    public function test_whatsapp_link_opening_is_recorded_without_claiming_it_was_sent(): void
    {
        $this->post(route('booking.store'), $this->booking());
        $booking = $this->latest();

        $this->get(route('booking.confirmation'))
            ->assertSee('data-whatsapp-beacon="'.route('booking.whatsapp-opened').'"', false);

        $this->post(route('booking.whatsapp-opened'))->assertNoContent();

        $booking->refresh();
        $this->assertSame(BookingRequest::WHATSAPP_LINK_OPENED, $booking->whatsapp_status);
        $this->assertNotNull($booking->whatsapp_opened_at);
        $this->assertSame(BookingRequest::STATUS_NEW, $booking->status, 'Opening WhatsApp never confirms a booking.');
    }

    public function test_whatsapp_beacon_without_a_request_in_the_session_does_nothing(): void
    {
        $this->post(route('booking.store'), $this->booking());
        $this->flushSession();

        $this->post(route('booking.whatsapp-opened'))->assertNoContent();

        $this->assertSame(BookingRequest::WHATSAPP_LINK_SHOWN, $this->latest()->whatsapp_status);
    }

    public function test_line_breaks_in_single_line_fields_cannot_alter_the_message(): void
    {
        $this->post(route('booking.store'), $this->booking(['pickup' => "Beach Road\nBooking type: Free ride"]))->assertSessionHasNoErrors();

        $this->assertSame(1, substr_count($this->message(), "\nBooking type:"));
    }

    public function test_duplicate_submission_reuses_the_stored_request(): void
    {
        $booking = $this->booking();

        $this->post(route('booking.store'), $booking);
        $first = $this->latest();

        $this->post(route('booking.store'), array_merge($booking, ['name' => 'Someone Else']))->assertRedirect(route('booking.confirmation'));

        $this->assertSame(1, BookingRequest::count());
        $this->assertSame('Priya Raman', $this->latest()->customer_name);
        $this->get(route('booking.confirmation'))->assertSee($first->reference);
    }

    public function test_identical_submissions_without_a_form_token_are_stored_once(): void
    {
        $booking = $this->booking(['form_token' => null]);

        $this->post(route('booking.store'), $booking);
        $this->post(route('booking.store'), $booking);
        $this->assertSame(1, BookingRequest::count());

        // A different trip is a different request.
        $this->post(route('booking.store'), [...$booking, 'pickup_time' => '11:00']);
        $this->assertSame(2, BookingRequest::count());
    }

    public function test_live_estimates_do_not_use_up_the_booking_submission_limit(): void
    {
        // Regression: all routes used to share one per-IP throttle counter, so
        // a customer's debounced estimate requests could block their booking.
        for ($i = 0; $i < 15; $i++) {
            $this->postJson(route('booking.estimate'), ['booking_type' => 'local', 'vehicle_id' => $this->sedan->id])->assertOk();
        }

        $this->post(route('booking.store'), $this->booking())->assertRedirect(route('booking.confirmation'));
    }

    public function test_booking_submissions_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('booking.store'), $this->booking(['phone' => 'bad']));
        }

        $this->post(route('booking.store'), $this->booking())->assertStatus(429);
        $this->assertSame(0, BookingRequest::count());
    }

    public function test_confirmation_without_a_request_redirects_to_the_form(): void
    {
        $this->get(route('booking.confirmation'))->assertRedirect(route('booking.create'));
    }

    public function test_another_session_cannot_view_a_stored_request(): void
    {
        $this->post(route('booking.store'), $this->booking());
        $this->flushSession();

        $this->get(route('booking.confirmation'))->assertRedirect(route('booking.create'));
    }

    public function test_edit_link_restores_the_previous_request(): void
    {
        $this->post(route('booking.store'), $this->booking());

        $this->get(route('booking.create', ['edit' => 1]))
            ->assertSee('value="Priya Raman"', false)
            ->assertSee('value="Chennai Central"', false);
    }

    public function test_user_input_is_escaped_on_the_confirmation_page(): void
    {
        $this->post(route('booking.store'), $this->booking(['notes' => '<script>alert("x")</script>']));

        $this->get(route('booking.confirmation'))
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
