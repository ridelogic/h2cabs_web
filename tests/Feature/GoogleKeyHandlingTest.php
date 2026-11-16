<?php

namespace Tests\Feature;

use App\Models\BookingRequest;
use App\Models\FareRule;
use App\Models\Vehicle;
use App\Places\GooglePlacesSearch;
use App\Places\GoogleRouteDistance;
use App\Places\RouteDistance;
use Database\Seeders\VehicleFareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Google keys: the browser key is the only key that may appear in a
 * response; server keys (Places, Routes) must never leave the server.
 */
class GoogleKeyHandlingTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'browser-key-AAA111';

    private const PLACES = 'server-places-key-BBB222';

    private const ROUTES = 'server-routes-key-CCC333';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VehicleFareSeeder::class);
        // Only approved fares are bookable.
        FareRule::query()->update(['is_approved' => true]);
        config([
            'services.google_maps.browser_key' => self::BROWSER,
            'services.google_places.key' => self::PLACES,
            'services.google_routes.key' => self::ROUTES,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function pages(): array
    {
        return ['home' => ['/'], 'booking' => ['/booking'], 'services' => ['/services'], 'about' => ['/about'], 'destinations' => ['/destinations'], 'contact' => ['/contact']];
    }

    #[DataProvider('pages')]
    public function test_server_keys_never_appear_in_html(string $url): void
    {
        $this->get($url)->assertOk()->assertDontSee(self::PLACES)->assertDontSee(self::ROUTES);
    }

    public function test_browser_key_appears_only_in_the_maps_loader_on_booking_pages(): void
    {
        $this->get('/booking')->assertSee('{key: "'.self::BROWSER.'"', false);
        $this->get('/services')->assertDontSee(self::BROWSER);
        $this->get('/about')->assertDontSee(self::BROWSER);
    }

    public function test_json_endpoints_never_return_keys(): void
    {
        Http::fake([
            GoogleRouteDistance::ENDPOINT => Http::response(['routes' => [['distanceMeters' => 50_000]]]),
            GooglePlacesSearch::ENDPOINT => Http::response(['suggestions' => []]),
        ]);
        $vehicleId = Vehicle::where('slug', 'sedan')->value('id');

        $estimate = $this->postJson(route('booking.estimate'), ['booking_type' => 'local', 'vehicle_id' => $vehicleId, 'pickup' => 'A place', 'dropoff' => 'B place'])
            ->assertOk()->getContent();
        $places = $this->getJson(route('places.search', ['q' => 'White Town']))->assertOk()->getContent();

        foreach ([self::BROWSER, self::PLACES, self::ROUTES] as $key) {
            $this->assertStringNotContainsString($key, $estimate);
            $this->assertStringNotContainsString($key, $places);
        }
    }

    public function test_a_server_key_reused_as_the_browser_key_is_never_published(): void
    {
        Log::spy();
        config(['services.google_maps.browser_key' => self::PLACES]);

        $this->get('/booking')
            ->assertDontSee(self::PLACES)
            ->assertDontSee('importLibrary', false)
            ->assertSee('data-place-mode="proxy"', false);

        Log::shouldHaveReceived('warning')->withArgs(fn ($message) => Str::contains($message, 'GOOGLE_MAPS_BROWSER_KEY matches a server-side Google key'));
    }

    public function test_distance_is_only_used_from_a_successful_route_response(): void
    {
        $origin = ['text' => 'Puducherry', 'place_id' => null];
        $destination = ['text' => 'Chennai', 'place_id' => 'ChIJmockChennai01'];

        $cases = [
            'no routes' => [['routes' => []], null],
            'zero distance' => [['routes' => [['distanceMeters' => 0]]], null],
            'non-numeric' => [['routes' => [['distanceMeters' => '150000']]], null],
            'valid' => [['routes' => [['distanceMeters' => 150_000]]], 150_000],
        ];

        $sequence = Http::sequence();
        foreach ($cases as [$body]) {
            $sequence->push($body);
        }
        Http::fake([GoogleRouteDistance::ENDPOINT => $sequence]);

        foreach ($cases as $label => [, $expected]) {
            $this->assertSame($expected, app(RouteDistance::class)->meters($origin, $destination), $label);
        }

        Http::assertSent(fn ($request) => $request['destination'] === ['placeId' => 'ChIJmockChennai01']
            && $request['origin'] === ['address' => 'Puducherry']
            && $request->hasHeader('X-Goog-Api-Key', self::ROUTES));
    }

    public function test_failed_google_requests_keep_a_chosen_location_booking_working(): void
    {
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);
        $vehicleId = Vehicle::where('slug', 'sedan')->value('id');
        $booking = [
            'booking_type' => 'outstation_one_way', 'vehicle_id' => $vehicleId,
            'pickup' => 'White Town, Puducherry', 'pickup_place_id' => 'ChIJmockPickup01',
            'dropoff' => 'Chennai Central', 'dropoff_place_id' => 'ChIJmockChennai01',
            'travel_date' => now('Asia/Kolkata')->addDays(2)->toDateString(), 'pickup_time' => '09:00',
            'passengers' => 2, 'name' => 'Route Down', 'phone' => '9876512345',
        ];

        // A failed search request is reported, never replaced by fake suggestions.
        $this->getJson(route('places.search', ['q' => 'White Town']))->assertStatus(502)->assertJsonMissingPath('results');

        // Typed text without a Google place is refused (no manual-entry fallback).
        $this->post(route('booking.store'), [...$booking, 'pickup_place_id' => ''])->assertSessionHasErrors('pickup_place_id');

        // Places chosen before the outage still book; the route distance is simply unknown.
        $this->post(route('booking.store'), $booking)->assertSessionHasNoErrors()->assertRedirect(route('booking.confirmation'));

        $stored = BookingRequest::query()->sole();
        $this->assertNull($stored->distance_meters);
        $this->assertSame('Fare will be confirmed after the route is verified', $stored->fare_summary);
    }
}
