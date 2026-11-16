<?php

namespace Tests\Feature;

use App\Places\GooglePlacesSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlaceSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_keys_location_search_is_unavailable_and_fields_stay_disabled(): void
    {
        config(['services.google_places.key' => null, 'services.google_maps.browser_key' => null]);

        $this->getJson(route('places.search', ['q' => 'White Town']))
            ->assertStatus(503)
            ->assertJson(['error' => 'unavailable']);

        foreach ([route('booking.create'), route('home')] as $url) {
            $html = $this->get($url)
                ->assertSee('Google location search is unavailable. Please try again later.')
                ->assertSee('data-places-retry', false)
                ->assertSee('data-place-mode="unavailable"', false)
                ->assertDontSee('role="combobox"', false)
                ->assertDontSee('maps.${c}apis.com', false)
                ->assertDontSee('powered by Google')
                ->assertDontSee('type the full address')
                ->assertDontSee('Type the full address')
                ->getContent();

            // The notice is visible (not hidden), both location inputs and the submit button are disabled.
            $this->assertMatchesRegularExpression('#<div data-places-notice role="alert"\s+class=#', $html, $url);
            $this->assertSame(2, preg_match_all('#name="(pickup|dropoff)"[^>]*\sdisabled aria-disabled="true"#', $html), $url);
            $this->assertMatchesRegularExpression('#data-submit disabled>#', $html, $url);
        }
    }

    public function test_with_google_configured_fields_start_disabled_until_search_is_ready(): void
    {
        foreach ([
            'proxy' => ['services.google_places.key' => 'test-secret-key-123', 'services.google_maps.browser_key' => null],
            'google' => ['services.google_places.key' => null, 'services.google_maps.browser_key' => 'browser-key-abc'],
        ] as $mode => $settings) {
            config($settings);

            foreach ([route('booking.create'), route('home')] as $url) {
                $html = $this->get($url)
                    ->assertSee('data-place-mode="'.$mode.'"', false)
                    ->assertSee('Loading Google location search…')
                    ->getContent();

                // Enabled only by place-autocomplete.js once Google is genuinely ready.
                $this->assertSame(2, preg_match_all('#name="(pickup|dropoff)"[^>]*\sdisabled aria-disabled="true"#', $html), "{$mode} {$url}");
                $this->assertMatchesRegularExpression('#<div data-places-notice role="alert"\s+hidden#', $html, "{$mode} {$url}");
                $this->assertMatchesRegularExpression('#data-submit disabled>#', $html, "{$mode} {$url}");
            }
        }
    }

    public function test_with_only_a_server_key_the_form_uses_the_server_proxy_without_exposing_the_key(): void
    {
        config(['services.google_places.key' => 'test-secret-key-123', 'services.google_maps.browser_key' => null]);

        $this->get(route('booking.create'))
            ->assertSee('role="combobox"', false)
            ->assertSee('data-place-mode="proxy"', false)
            ->assertSee('data-endpoint="'.route('places.search').'"', false)
            ->assertSee('powered by Google')
            ->assertDontSee('test-secret-key-123')
            ->assertDontSee('importLibrary', false);
    }

    public function test_with_a_browser_key_the_maps_javascript_loader_is_rendered_once(): void
    {
        config(['services.google_maps.browser_key' => 'browser-key-abc', 'services.google_places.key' => 'server-secret-xyz']);

        $html = $this->get(route('booking.create'))
            ->assertSee('data-place-mode="google"', false)
            ->assertSee('{key: "browser-key-abc", v: "weekly", region: "IN", language: "en"}', false)
            ->assertSee('window.gm_authFailure', false)
            ->assertDontSee('server-secret-xyz')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'importLibrary="importLibrary"') + substr_count($html, 'l="importLibrary"'));
        $this->assertSame(2, substr_count($html, 'data-place-mode="google"'), 'Pickup and destination are separate autocomplete fields.');
    }

    public function test_browser_key_is_escaped_in_the_loader(): void
    {
        config(['services.google_maps.browser_key' => '"</script><script>alert(1)</script>']);

        $this->get(route('booking.create'))->assertDontSee('</script><script>alert(1)', false);
    }

    public function test_search_returns_suggestions_from_google(): void
    {
        config(['services.google_places.key' => 'test-secret-key-123']);

        Http::fake([
            GooglePlacesSearch::ENDPOINT => Http::response([
                'suggestions' => [
                    ['placePrediction' => [
                        'placeId' => 'ChIJabc1234567',
                        'text' => ['text' => 'Rock Beach, Puducherry, India'],
                        'structuredFormat' => [
                            'mainText' => ['text' => 'Rock Beach'],
                            'secondaryText' => ['text' => 'Puducherry, India'],
                        ],
                    ]],
                    ['queryPrediction' => ['text' => ['text' => 'rock beach cafes']]],
                ],
            ]),
        ]);

        $this->getJson(route('places.search', ['q' => 'Rock Be', 'session' => 'b5c3d0e2-1111-4222-8333-944455556666']))
            ->assertOk()
            ->assertExactJson(['results' => [[
                'id' => 'ChIJabc1234567',
                'label' => 'Rock Beach, Puducherry, India',
                'main' => 'Rock Beach',
                'secondary' => 'Puducherry, India',
            ]]]);

        Http::assertSent(fn (Request $request) => $request->hasHeader('X-Goog-Api-Key', 'test-secret-key-123')
            && $request['input'] === 'Rock Be'
            && $request['includedRegionCodes'] === ['in']
            && $request['sessionToken'] === 'b5c3d0e2-1111-4222-8333-944455556666'
            && isset($request['locationBias']['circle']['center']['latitude']));
    }

    public function test_provider_failure_returns_a_friendly_error(): void
    {
        config(['services.google_places.key' => 'test-secret-key-123']);
        Http::fake([GooglePlacesSearch::ENDPOINT => Http::response(['error' => ['message' => 'API key invalid']], 403)]);

        $this->getJson(route('places.search', ['q' => 'White Town']))
            ->assertStatus(502)
            ->assertJson(['error' => 'failed'])
            ->assertJsonMissing(['message' => 'API key invalid']);
    }

    public function test_query_is_validated(): void
    {
        config(['services.google_places.key' => 'test-secret-key-123']);
        Http::fake();

        $this->getJson(route('places.search', ['q' => 'ab']))->assertStatus(422);
        $this->getJson(route('places.search', ['q' => str_repeat('a', 121)]))->assertStatus(422);
        $this->getJson(route('places.search', ['q' => 'White Town', 'session' => 'bad token!']))->assertStatus(422);

        Http::assertNothingSent();
    }
}
