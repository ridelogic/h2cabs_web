<?php

namespace App\Content;

/**
 * Homepage copy that is not part of the service/destination catalogues.
 *
 * Kept out of the Blade views so it can later be swapped for database/CMS
 * content without touching markup. Icons reference resources/views/components/ui/icon.blade.php.
 */
class HomePageContent
{
    /** @return list<array{icon: string, title: string, text: string}> */
    public function benefits(): array
    {
        return [
            [
                'icon' => 'users',
                'title' => 'Local association drivers',
                'text' => 'Rides are driven by members of the Helping Hand Drivers Association who live and work in Puducherry.',
            ],
            [
                'icon' => 'map',
                'title' => 'Know Pondicherry',
                'text' => 'Drivers who know the town, the East Coast Road and the quickest way through the old streets.',
            ],
            [
                'icon' => 'receipt',
                'title' => 'Fare agreed upfront',
                'text' => 'We share the fare for your trip before you confirm, so there are no surprises at drop-off.',
            ],
            [
                'icon' => 'chat',
                'title' => 'Easy WhatsApp booking',
                'text' => 'Fill in the booking form and send the details to us on WhatsApp in one tap.',
            ],
        ];
    }

    /**
     * How the WhatsApp booking flow works. Kept accurate to what the site
     * actually does: it prepares a message; the team replies to confirm.
     *
     * @return list<array{title: string, text: string}>
     */
    public function bookingSteps(): array
    {
        return [
            ['title' => 'Choose your trip', 'text' => 'Local, Rental, Outstation One Way or Round Trip.'],
            ['title' => 'Route, time & vehicle', 'text' => 'Add pickup, destination and dates, pick a vehicle and see an estimate.'],
            ['title' => 'Send on WhatsApp', 'text' => 'We prepare the message — you press Send in WhatsApp.'],
            ['title' => 'Get confirmation', 'text' => 'Our team replies to confirm the car, driver and final fare.'],
        ];
    }
}
