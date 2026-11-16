<?php

namespace App\Support;

use App\Models\Setting;
use Closure;

/**
 * The website settings administrators may edit, and how they map onto
 * config('site.*'). A saved value overrides the .env default; clearing it
 * restores the .env value. Nothing here has a built-in default value.
 */
class SiteSettings
{
    /** @var array<string, mixed> Config values as they were before dashboard overrides. */
    private static array $original = [];

    /**
     * key (config path under "site.") => [group, label, rules, help]
     *
     * @return array<string, array{group: string, label: string, rules: array<int, mixed>, help: string, type: string}>
     */
    public static function definitions(): array
    {
        $https = fn (array $hosts) => ['nullable', 'string', 'max:500', 'url:https', self::hostIn($hosts)];

        return [
            'contact.phone' => ['group' => 'Contact', 'label' => 'Phone number', 'type' => 'tel', 'rules' => ['nullable', 'string', 'max:20', 'regex:/^[+()\d\s-]{7,20}$/'], 'help' => 'Shown in the header, footer and contact page.'],
            'contact.whatsapp' => ['group' => 'Contact', 'label' => 'Business WhatsApp number', 'type' => 'tel', 'rules' => ['nullable', 'string', 'max:20', self::validWhatsApp(...)], 'help' => 'Enables WhatsApp booking, e.g. +91 98765 43210. Leave blank to disable.'],
            'contact.email' => ['group' => 'Contact', 'label' => 'Email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:150'], 'help' => ''],
            'contact.address' => ['group' => 'Contact', 'label' => 'Office address', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:200'], 'help' => ''],
            'contact.hours' => ['group' => 'Contact', 'label' => 'Opening hours', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'help' => 'e.g. Open 24 hours, or 6 am – 11 pm'],

            'apps.passenger.android' => ['group' => 'App links', 'label' => 'Passenger app — Google Play URL', 'type' => 'url', 'rules' => $https(['play.google.com']), 'help' => 'Only add once the app is published. Enables the store button and QR code.'],
            'apps.passenger.ios' => ['group' => 'App links', 'label' => 'Passenger app — App Store URL', 'type' => 'url', 'rules' => $https(['apps.apple.com']), 'help' => 'Only add once the app is published.'],
            'apps.driver.android' => ['group' => 'App links', 'label' => 'Driver app — Google Play URL', 'type' => 'url', 'rules' => $https(['play.google.com']), 'help' => ''],
            'apps.driver.ios' => ['group' => 'App links', 'label' => 'Driver app — App Store URL', 'type' => 'url', 'rules' => $https(['apps.apple.com']), 'help' => ''],

            'social.facebook' => ['group' => 'Social links', 'label' => 'Facebook page', 'type' => 'url', 'rules' => $https(['facebook.com', 'www.facebook.com', 'm.facebook.com']), 'help' => ''],
            'social.instagram' => ['group' => 'Social links', 'label' => 'Instagram profile', 'type' => 'url', 'rules' => $https(['instagram.com', 'www.instagram.com']), 'help' => ''],
            'social.youtube' => ['group' => 'Social links', 'label' => 'YouTube channel', 'type' => 'url', 'rules' => $https(['youtube.com', 'www.youtube.com', 'youtu.be']), 'help' => ''],
        ];
    }

    /** Overlay saved settings onto config('site.*'). Called once per request. */
    public static function apply(): void
    {
        $definitions = self::definitions();
        self::$original = [];

        foreach (Setting::values() as $key => $value) {
            if (isset($definitions[$key]) || $key === 'brand.logo_mark') {
                self::$original[$key] ??= config('site.'.$key);
                config(['site.'.$key => $value]);
            }
        }
    }

    /**
     * The value from .env / config files, ignoring dashboard overrides.
     * (Captured before overriding, so it also works with config:cache.)
     */
    public static function envDefault(string $key): ?string
    {
        $value = array_key_exists($key, self::$original) ? self::$original[$key] : config('site.'.$key);

        return filled($value) ? (string) $value : null;
    }

    private static function hostIn(array $hosts): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($hosts) {
            if (is_string($value) && $value !== '' && ! in_array(strtolower((string) parse_url($value, PHP_URL_HOST)), $hosts, true)) {
                $fail('This link must be on '.implode(' or ', array_unique(array_map(fn ($h) => preg_replace('/^(www|m)\./', '', $h), $hosts))).'.');
            }
        };
    }

    private static function validWhatsApp(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value) && WhatsApp::normalize((string) $value) === null) {
            $fail('Enter a valid WhatsApp number, e.g. +91 98765 43210.');
        }
    }
}
