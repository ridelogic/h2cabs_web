<?php

namespace App\Support;

/**
 * Builds wa.me click-to-chat links for the business WhatsApp number.
 *
 * A wa.me link only pre-fills a message; the customer still has to press
 * Send in WhatsApp. Nothing here confirms that a message was delivered.
 */
class WhatsApp
{
    /**
     * Normalise a phone number to the digits-only international format
     * wa.me expects (e.g. "919876543210"). Ten-digit numbers are treated as
     * Indian mobiles. Returns null for anything that is not a usable number.
     */
    public static function normalize(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = preg_match('/^[6-9]/', $digits) ? '91'.$digits : null;
        }

        return $digits !== null && preg_match('/^[1-9]\d{9,14}$/', $digits) ? $digits : null;
    }

    public static function businessNumber(): ?string
    {
        return self::normalize(config('site.contact.whatsapp'));
    }

    public static function isConfigured(): bool
    {
        return self::businessNumber() !== null;
    }

    /** Click-to-chat URL for the business number, or null when it is not configured. */
    public static function chatUrl(string $message = ''): ?string
    {
        $number = self::businessNumber();

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.($message === '' ? '' : '?text='.rawurlencode($message));
    }
}
