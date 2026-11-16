<?php

namespace App\Providers;

use App\Models\BookingRequest;
use App\Places\GooglePlacesSearch;
use App\Places\GoogleRouteDistance;
use App\Places\NullPlaceSearch;
use App\Places\NullRouteDistance;
use App\Places\PlaceSearch;
use App\Places\RouteDistance;
use App\Support\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Location search is unavailable (fields disabled) unless a provider key is configured.
        $this->app->bind(PlaceSearch::class, function () {
            $key = config('services.google_places.key');

            if (config('site.places.provider') === 'google' && filled($key)) {
                return new GooglePlacesSearch($key, (int) config('services.google_places.timeout', 4));
            }

            return new NullPlaceSearch;
        });

        // Route distance (for fare estimates) is unknown unless a Routes API key is configured.
        $this->app->bind(RouteDistance::class, function () {
            $key = config('services.google_routes.key');

            return filled($key)
                ? new GoogleRouteDistance($key, (int) config('services.google_routes.timeout', 5))
                : new NullRouteDistance;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Website settings saved in the admin dashboard override .env defaults.
        SiteSettings::apply();

        // Admin shell: the count of new (unhandled) booking requests for the
        // sidebar badge and the top-bar bell. (The notifications list itself
        // is fetched when the bell is opened: Admin\NotificationController.)
        View::composer('components.layouts.admin', function ($view) {
            try {
                $view->with('adminNewBookings', BookingRequest::query()->where('status', BookingRequest::STATUS_NEW)->count());
            } catch (QueryException) {
                $view->with('adminNewBookings', 0);
            }
        });

        // Separate limits per endpoint. (Plain "throttle:N,1" shares one counter
        // per IP across routes, so live estimate and location-search requests
        // would use up the allowance for submitting a booking.)
        foreach ([
            'booking-submit' => 10,
            'booking-estimate' => 30,
            'booking-whatsapp' => 20,
            'places-search' => 40,
            'admin-login' => 10,
        ] as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($perMinute)->by($request->ip()));
        }
    }
}
