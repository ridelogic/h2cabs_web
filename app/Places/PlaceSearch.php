<?php

namespace App\Places;

/**
 * Location suggestions for the booking form's pickup/drop-off fields.
 * Implementations run server-side so provider keys never reach the browser.
 */
interface PlaceSearch
{
    /** Whether a provider is configured. When false, the booking form disables location search. */
    public function available(): bool;

    /**
     * @return list<PlaceSuggestion>
     *
     * @throws PlaceSearchException when the provider fails or rejects the request
     */
    public function search(string $query, ?string $sessionToken = null): array;
}
