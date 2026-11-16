<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingType;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\FareRule;
use App\Models\Member;
use App\Models\Vehicle;
use App\Support\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'members' => Member::count(),
                'publicMembers' => Member::query()->published()->count(),
                'vehicles' => Vehicle::where('is_active', true)->count(),
                'bookableVehicles' => $this->bookableVehicleCount(),
                'fares' => FareRule::where('is_active', true)->count(),
                'proposedFares' => FareRule::where('is_active', true)->where('is_approved', false)->count(),
                'bookings' => BookingRequest::count(),
                'newBookings' => BookingRequest::where('status', BookingRequest::STATUS_NEW)->count(),
            ],
            'recentBookings' => BookingRequest::query()->latest('id')->limit(5)->get(),
            // Only whether each item is set — never the values themselves.
            'checks' => [
                [
                    'title' => 'WhatsApp number',
                    'source' => 'Settings or H2CABS_WHATSAPP',
                    'help' => 'Customers send their booking request to this number.',
                    'icon' => 'whatsapp',
                    'ok' => WhatsApp::isConfigured(),
                    'link' => route('admin.settings.edit'),
                    'action' => 'Configure',
                ],
                [
                    'title' => 'Location search in browser',
                    'source' => 'GOOGLE_MAPS_BROWSER_KEY',
                    'help' => 'Google autocomplete in the booking form (referrer-restricted key).',
                    'icon' => 'pin',
                    'ok' => filled(config('services.google_maps.browser_key')),
                    'link' => 'https://developers.google.com/maps/documentation/javascript/get-api-key',
                    'action' => 'Docs',
                    'hint' => 'Set in .env',
                ],
                [
                    'title' => 'Location search via server',
                    'source' => 'GOOGLE_PLACES_API_KEY',
                    'help' => 'Server-side place search, used when the browser key is not set.',
                    'icon' => 'server',
                    'ok' => filled(config('services.google_places.key')),
                    'link' => 'https://developers.google.com/maps/documentation/places/web-service/get-api-key',
                    'action' => 'Docs',
                    'hint' => 'Set in .env',
                ],
                [
                    'title' => 'Route distance',
                    'source' => 'GOOGLE_ROUTES_API_KEY',
                    'help' => 'Road distance for live fare estimates.',
                    'icon' => 'route',
                    'ok' => filled(config('services.google_routes.key')),
                    'link' => 'https://developers.google.com/maps/documentation/routes/get-api-key',
                    'action' => 'Docs',
                    'hint' => 'Set in .env',
                ],
                [
                    'title' => 'Public storage link',
                    'source' => 'php artisan storage:link',
                    'help' => 'Serves uploaded vehicle, member and logo images.',
                    'icon' => 'database',
                    'ok' => is_dir(public_path('storage')),
                    'link' => 'https://laravel.com/docs/filesystem#the-public-disk',
                    'action' => 'Docs',
                    'hint' => 'Run the command once',
                ],
            ],
        ]);
    }

    /** Active vehicles customers can book for at least one trip type. */
    private function bookableVehicleCount(): int
    {
        return Vehicle::query()->where('is_active', true)
            ->where(function (Builder $query) {
                foreach (BookingType::cases() as $type) {
                    $query->orWhereHas('fareRules', fn (Builder $rules) => $rules->bookable($type));
                }
            })
            ->count();
    }
}
