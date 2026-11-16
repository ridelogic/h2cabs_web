<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingType;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\FareRule;
use App\Models\Member;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Top-bar search across booking requests, members, vehicles and fares.
 * Each group shows a few matches and links to the full screen.
 */
class SearchController extends Controller
{
    private const LIMIT = 6;

    public function __invoke(Request $request): View
    {
        $term = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));

        if ($term === '') {
            return view('admin.search', ['term' => '', 'results' => null, 'total' => 0]);
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        // Fares are found by vehicle name or trip type ("Outstation", "Rental", ...).
        $types = collect(BookingType::cases())
            ->filter(fn (BookingType $type) => str_contains(mb_strtolower($type->label()), mb_strtolower($term)))
            ->map->value
            ->values();

        $results = [
            'bookings' => BookingRequest::query()->search($term)->latest('id')->limit(self::LIMIT)->get(),
            'members' => Member::query()
                ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('role', 'like', $like)->orWhere('phone', 'like', $like))
                ->ordered()->limit(self::LIMIT)->get(),
            'vehicles' => Vehicle::query()
                ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('category', 'like', $like))
                ->orderBy('sort_order')->orderBy('name')->limit(self::LIMIT)->get(),
            'fares' => FareRule::query()->with('vehicle')
                ->where(fn (Builder $q) => $q->whereHas('vehicle', fn (Builder $v) => $v->where('name', 'like', $like))
                    ->when($types->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('booking_type', $types)))
                ->latest('id')->limit(self::LIMIT)->get(),
        ];

        return view('admin.search', [
            'term' => $term,
            'results' => $results,
            'total' => collect($results)->sum->count(),
        ]);
    }
}
