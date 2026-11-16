<?php

/*
|--------------------------------------------------------------------------
| H2 CABS site configuration
|--------------------------------------------------------------------------
|
| Brand, contact and SEO defaults for the public website. Contact details
| and app store links are read from the environment so nothing real is
| hardcoded; anything left empty is hidden or shown as "coming soon" in
| the views rather than faked. A future admin/CMS can override these.
|
*/

return [

    'brand' => [
        'name' => 'H2 CABS',
        'full_name' => 'Helping Hand Drivers Association Cabs',
        'tagline' => 'Driven by Pondicherry\'s own drivers',
        'logo' => 'images/brand/h2cabs-logo.svg',
        'logo_mark' => 'images/brand/h2cabs-mark.svg',
    ],

    // The association's name exactly as published on the reference website.
    'association' => [
        'name' => "Helping Hands Driver's Association – Puducherry",
    ],

    'region' => [
        'city' => 'Puducherry',
        'city_alt' => 'Pondicherry',
        'state' => 'Puducherry',
        'country' => 'IN',
    ],

    'contact' => [
        'phone' => env('H2CABS_PHONE'),
        'whatsapp' => env('H2CABS_WHATSAPP'),
        'email' => env('H2CABS_EMAIL'),
        'address' => env('H2CABS_ADDRESS'),
        'hours' => env('H2CABS_HOURS'),
    ],

    'apps' => [
        'passenger' => [
            'android' => env('H2CABS_PASSENGER_ANDROID_URL'),
            'ios' => env('H2CABS_PASSENGER_IOS_URL'),
        ],
        'driver' => [
            'android' => env('H2CABS_DRIVER_ANDROID_URL'),
            'ios' => env('H2CABS_DRIVER_IOS_URL'),
        ],
    ],

    /*
    | Primary navigation. Every entry is a named route; `active` is the
    | route pattern that marks the item as the current page.
    */
    'navigation' => [
        ['label' => 'Home', 'route' => 'home', 'active' => 'home'],
        ['label' => 'About Us', 'route' => 'about', 'active' => 'about'],
        ['label' => 'Our Services', 'route' => 'services', 'active' => 'services'],
        ['label' => 'Destinations', 'route' => 'destinations', 'active' => 'destinations'],
        ['label' => 'Booking', 'route' => 'booking.create', 'active' => 'booking.*'],
        ['label' => 'Contact Us', 'route' => 'contact', 'active' => 'contact'],
    ],

    /*
    | Links in the top header bar. `params` are added to the route URL;
    | `type` marks the item active on the booking page for that trip type.
    | The footer keeps the full `navigation` list above.
    */
    'header_navigation' => [
        ['label' => 'Home', 'route' => 'home', 'active' => 'home'],
        ['label' => 'About Us', 'route' => 'about', 'active' => 'about'],
        ['label' => 'Services', 'route' => 'services', 'active' => 'services'],
        ['label' => 'Local Taxi', 'route' => 'booking.create', 'params' => ['type' => 'local'], 'active' => 'booking.*', 'type' => 'local'],
        ['label' => 'Rental', 'route' => 'booking.create', 'params' => ['type' => 'rental'], 'active' => 'booking.*', 'type' => 'rental'],
        ['label' => 'Outstation', 'route' => 'booking.create', 'params' => ['type' => 'outstation_one_way'], 'active' => 'booking.*', 'type' => ['outstation_one_way', 'outstation_round_trip']],
        ['label' => 'Contact', 'route' => 'contact', 'active' => 'contact'],
    ],

    'home' => [
        // Homepage hero scene: the silver sedan on the Puducherry waterfront at
        // night (lighthouse, palms, streetlights). Shown bottom-right and faded
        // into the blue gradient. Path under public/; override with
        // H2CABS_HERO_CAR_IMAGE if the artwork is replaced.
        'hero_car' => env('H2CABS_HERO_CAR_IMAGE', 'images/hero/h2cabs-hero.webp'),
    ],

    'booking' => [
        // Dates and "today" checks use local time in Puducherry.
        'timezone' => 'Asia/Kolkata',
        'max_passengers' => 12,
        'airports' => [
            'PNY' => 'Puducherry Airport (PNY)',
            'MAA' => 'Chennai International Airport (MAA)',
        ],
    ],

    /*
    | Location search for the booking form. Suggestions are fetched through
    | the server (/places/search), so the provider key never reaches the
    | browser. With no key configured the fields fall back to manual entry.
    */
    'places' => [
        'provider' => env('PLACES_PROVIDER', 'google'),
        'bias' => [
            'latitude' => 11.9416,
            'longitude' => 79.8083,
            'radius_meters' => 50000,
        ],
        'region' => 'in',
        'min_query_length' => 3,
    ],

    'social' => [
        'facebook' => env('H2CABS_FACEBOOK_URL'),
        'twitter' => env('H2CABS_TWITTER_URL'),
        'instagram' => env('H2CABS_INSTAGRAM_URL'),
        'youtube' => env('H2CABS_YOUTUBE_URL'),
    ],

    'seo' => [
        'title_suffix' => 'H2 CABS',
        'default_title' => 'H2 CABS — Taxi Service in Pondicherry',
        'default_description' => 'Book local rides, hourly rentals and outstation one-way or round trips in Pondicherry with H2 CABS, a cab service run by the Helping Hand Drivers Association.',
        'default_image' => 'images/og/h2cabs-og.png',
        'locale' => 'en_IN',
        // Search engines are only allowed to index production.
        'indexable' => env('APP_ENV') === 'production',

        /*
        | Default title (suffixed with " | H2 CABS") and meta description per
        | public page, by route name. Admins can override these in
        | Admin → SEO; a null title uses default_title (homepage).
        */
        'pages' => [
            'home' => ['label' => 'Home', 'title' => null, 'description' => null],
            'about' => ['label' => 'About Us', 'title' => 'About the Helping Hand Drivers Association', 'description' => 'H2 CABS is run by the Helping Hand Drivers Association, a group of local drivers in Puducherry. Learn about our mission, vision and members.'],
            'services' => ['label' => 'Our Services', 'title' => 'Taxi Services in Pondicherry', 'description' => 'Local rides, hourly rentals, and outstation one-way and round-trip taxis from Puducherry in Sedan or SUV. See vehicle rates and fare rules, then book on WhatsApp.'],
            'destinations' => ['label' => 'Destinations', 'title' => 'Places to Visit in Pondicherry', 'description' => 'A local guide to Promenade and Rock Beach, the French Quarter, Auroville, Paradise Beach, Chunnambar and more — with a driver from H2 CABS.'],
            'booking.create' => ['label' => 'Booking', 'title' => 'Book a Taxi in Pondicherry', 'description' => 'Book a local ride, rental, outstation one-way or round-trip taxi from Puducherry. Choose your vehicle, see an estimate and send your request to H2 CABS on WhatsApp.'],
            'contact' => ['label' => 'Contact Us', 'title' => 'Contact Us', 'description' => 'Contact H2 CABS in Puducherry to book a taxi, ask about a trip or partner with the Helping Hand Drivers Association.'],
            'photo-credits' => ['label' => 'Photo credits', 'title' => 'Photo Credits', 'description' => 'Credits and licences for the photographs used on the H2 CABS website.'],
        ],
    ],

    /*
    | Named routes listed in /sitemap.xml. Add new public pages here; dynamic
    | entries (e.g. CMS pages) can be appended in SitemapController later.
    */
    'sitemap_routes' => [
        'home' => ['changefreq' => 'weekly', 'priority' => '1.0'],
        'booking.create' => ['changefreq' => 'monthly', 'priority' => '0.9'],
        'services' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        'destinations' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        'about' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        'contact' => ['changefreq' => 'monthly', 'priority' => '0.6'],
        'photo-credits' => ['changefreq' => 'yearly', 'priority' => '0.2'],
    ],

];
