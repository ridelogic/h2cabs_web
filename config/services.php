<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Server-side key for Places API (New) autocomplete. Restrict it to
    // the Places API and to your server's IP address in Google Cloud.
    'google_places' => [
        'key' => env('GOOGLE_PLACES_API_KEY'),
        'timeout' => 4,
    ],

    // Browser key for Places autocomplete via the Maps JavaScript API. This key
    // is visible in page source by design: restrict it in Google Cloud to your
    // website's HTTP referrers and to the Maps JavaScript API + Places API (New).
    'google_maps' => [
        'browser_key' => env('GOOGLE_MAPS_BROWSER_KEY'),
    ],

    // Server-side key for the Routes API (road distance for fare estimates).
    // May be the same key as Places if both APIs are enabled on it.
    'google_routes' => [
        'key' => env('GOOGLE_ROUTES_API_KEY', env('GOOGLE_PLACES_API_KEY')),
        'timeout' => 5,
    ],

];
