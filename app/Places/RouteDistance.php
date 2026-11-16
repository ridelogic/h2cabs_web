<?php

namespace App\Places;

/**
 * Road distance between two locations, calculated on the server. When no
 * provider is configured, distance is unknown and fares are shown as
 * "to be confirmed" — a distance is never guessed.
 */
interface RouteDistance
{
    public function available(): bool;

    /**
     * @param  array{text: string, place_id: ?string}  $origin
     * @param  array{text: string, place_id: ?string}  $destination
     * @return int|null Driving distance in metres, or null if no route was found.
     *
     * @throws PlaceSearchException when the provider fails
     */
    public function meters(array $origin, array $destination): ?int;
}
