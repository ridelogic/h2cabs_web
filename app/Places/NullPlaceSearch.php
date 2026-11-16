<?php

namespace App\Places;

/** Used when no provider is configured: location fields stay disabled on the booking form. */
class NullPlaceSearch implements PlaceSearch
{
    public function available(): bool
    {
        return false;
    }

    public function search(string $query, ?string $sessionToken = null): array
    {
        return [];
    }
}
