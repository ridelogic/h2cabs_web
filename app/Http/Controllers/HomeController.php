<?php

namespace App\Http\Controllers;

use App\Booking\BookingFormData;
use App\Content\DestinationCatalog;
use App\Content\ServiceCatalog;
use App\Support\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, BookingFormData $form, ServiceCatalog $services, DestinationCatalog $destinations): View
    {
        $booking = $form->make($request);

        return view('pages.home', [
            'seo' => SeoMeta::page('home'),
            'booking' => $booking,
            // Every active vehicle (the admin Active toggle controls visibility).
            // Vehicles without a bookable fare show "Fare available on request"
            // and cannot be selected in the booking form until a fare is set.
            'vehicles' => $booking['vehicles'],
            'services' => $services->primary(),
            'routes' => $services->outstationRoutes(),
            'destinations' => array_slice($destinations->all(), 0, 5),
        ]);
    }
}
