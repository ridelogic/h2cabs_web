<?php

namespace App\Places;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Places API (New) autocomplete, called from the server.
 *
 * Results are biased to the Puducherry area and restricted to India.
 * A client-generated session token groups keystrokes into one billing
 * session. Responses are not cached, in line with Google's terms.
 *
 * @see https://developers.google.com/maps/documentation/places/web-service/place-autocomplete
 */
class GooglePlacesSearch implements PlaceSearch
{
    public const ENDPOINT = 'https://places.googleapis.com/v1/places:autocomplete';

    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeout = 4,
    ) {}

    public function available(): bool
    {
        return true;
    }

    public function search(string $query, ?string $sessionToken = null): array
    {
        $bias = config('site.places.bias');

        $payload = array_filter([
            'input' => $query,
            'languageCode' => 'en',
            'includedRegionCodes' => [config('site.places.region')],
            'locationBias' => [
                'circle' => [
                    'center' => ['latitude' => $bias['latitude'], 'longitude' => $bias['longitude']],
                    'radius' => (float) $bias['radius_meters'],
                ],
            ],
            'sessionToken' => $sessionToken,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-Goog-Api-Key' => $this->apiKey])
                ->acceptJson()
                ->post(self::ENDPOINT, $payload);
        } catch (ConnectionException $e) {
            throw new PlaceSearchException('Location search is unreachable.', previous: $e);
        }

        if ($response->failed()) {
            // Log status only; the body can echo request details.
            Log::warning('Google Places autocomplete failed', ['status' => $response->status()]);

            throw new PlaceSearchException('Location search failed.');
        }

        return collect($response->json('suggestions', []))
            ->pluck('placePrediction')
            ->filter()
            ->map(fn (array $prediction) => new PlaceSuggestion(
                id: $prediction['placeId'],
                label: $prediction['text']['text'] ?? $prediction['structuredFormat']['mainText']['text'] ?? '',
                main: $prediction['structuredFormat']['mainText']['text'] ?? $prediction['text']['text'] ?? '',
                secondary: $prediction['structuredFormat']['secondaryText']['text'] ?? null,
            ))
            ->filter(fn (PlaceSuggestion $suggestion) => $suggestion->id !== '' && $suggestion->label !== '')
            ->values()
            ->all();
    }
}
