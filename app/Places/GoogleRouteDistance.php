<?php

namespace App\Places;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driving distance from the Google Routes API (computeRoutes), called
 * server-side. Uses place IDs from location search when available,
 * otherwise the typed address. Only the distance field is requested.
 *
 * @see https://developers.google.com/maps/documentation/routes/compute_route_directions
 */
class GoogleRouteDistance implements RouteDistance
{
    public const ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeout = 5,
    ) {}

    public function available(): bool
    {
        return true;
    }

    public function meters(array $origin, array $destination): ?int
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Goog-Api-Key' => $this->apiKey,
                    'X-Goog-FieldMask' => 'routes.distanceMeters',
                ])
                ->acceptJson()
                ->post(self::ENDPOINT, [
                    'origin' => $this->waypoint($origin),
                    'destination' => $this->waypoint($destination),
                    'travelMode' => 'DRIVE',
                    'regionCode' => config('site.places.region'),
                ]);
        } catch (ConnectionException $e) {
            throw new PlaceSearchException('Route distance is unreachable.', previous: $e);
        }

        if ($response->failed()) {
            Log::warning('Google Routes request failed', ['status' => $response->status()]);

            throw new PlaceSearchException('Route distance failed.');
        }

        $meters = $response->json('routes.0.distanceMeters');

        return is_int($meters) && $meters > 0 ? $meters : null;
    }

    /**
     * @param  array{text: string, place_id: ?string}  $location
     * @return array<string, string>
     */
    private function waypoint(array $location): array
    {
        return $location['place_id']
            ? ['placeId' => $location['place_id']]
            : ['address' => $location['text']];
    }
}
