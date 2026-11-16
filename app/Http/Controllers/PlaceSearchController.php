<?php

namespace App\Http\Controllers;

use App\Places\PlaceSearch;
use App\Places\PlaceSearchException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoint behind the booking form's location autocomplete.
 * Rate limited in routes/web.php to keep provider costs predictable.
 */
class PlaceSearchController extends Controller
{
    public function __invoke(Request $request, PlaceSearch $places): JsonResponse
    {
        // Background request: keep flashed errors/old input for the next page view.
        $request->session()->reflash();

        if (! $places->available()) {
            return response()->json(['error' => 'unavailable', 'message' => 'Location search is not available. Please type the address.'], 503);
        }

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:'.config('site.places.min_query_length'), 'max:120'],
            'session' => ['nullable', 'string', 'regex:/^[A-Za-z0-9-]{8,36}$/'],
        ]);

        try {
            $results = $places->search($validated['q'], $validated['session'] ?? null);
        } catch (PlaceSearchException) {
            return response()->json(['error' => 'failed', 'message' => 'Location search is not responding. Please type the address.'], 502);
        }

        return response()->json(['results' => $results]);
    }
}
