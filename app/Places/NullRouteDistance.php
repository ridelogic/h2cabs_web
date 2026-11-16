<?php

namespace App\Places;

class NullRouteDistance implements RouteDistance
{
    public function available(): bool
    {
        return false;
    }

    public function meters(array $origin, array $destination): ?int
    {
        return null;
    }
}
