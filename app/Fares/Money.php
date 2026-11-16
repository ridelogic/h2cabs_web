<?php

namespace App\Fares;

use InvalidArgumentException;

/**
 * Rupee amounts as integer paise, so fare arithmetic never uses floats.
 */
final class Money
{
    /** "13.5" | "13.50" | 13 → 1350 paise. Null stays null. */
    public static function toPaise(string|int|null $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $amount = trim((string) $amount);

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $m)) {
            throw new InvalidArgumentException("Invalid rupee amount: {$amount}");
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    /** "13.5" km → 135 tenths of a km. */
    public static function toTenths(string|int|null $km): ?int
    {
        if ($km === null || $km === '') {
            return null;
        }

        $km = trim((string) $km);

        if (! preg_match('/^(\d+)(?:\.(\d))?\d*$/', $km, $m)) {
            throw new InvalidArgumentException("Invalid distance: {$km}");
        }

        return (int) $m[1] * 10 + (int) ($m[2] ?? 0);
    }

    /** Integer division rounding half up (inputs are non-negative). */
    public static function divideRounded(int $numerator, int $denominator): int
    {
        return intdiv($numerator * 2 + $denominator, $denominator * 2);
    }

    /**
     * Indian rupee formatting: 123456.5 → "₹1,23,456.50". Paise are shown
     * only when non-zero unless $forcePaise.
     */
    public static function format(int $paise, bool $forcePaise = false): string
    {
        $rupees = intdiv($paise, 100);
        $fraction = $paise % 100;

        $digits = (string) $rupees;
        $lastThree = substr($digits, -3);
        $rest = substr($digits, 0, -3);
        $grouped = $rest === '' ? $lastThree : preg_replace('/\B(?=(\d{2})+$)/', ',', $rest).','.$lastThree;

        return '₹'.$grouped.($fraction || $forcePaise ? '.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT) : '');
    }

    /** 135 tenths → "13.5", 130 → "13". */
    public static function formatKm(int $tenths): string
    {
        return $tenths % 10 === 0 ? (string) intdiv($tenths, 10) : intdiv($tenths, 10).'.'.($tenths % 10);
    }
}
