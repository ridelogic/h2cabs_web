<?php

namespace App\Enums;

/**
 * Flexible rental durations from the reference website, used when no
 * priced rental packages are configured for a vehicle. They carry no
 * price; the fare is confirmed by the team.
 */
enum RentalDuration: string
{
    case FewHours = 'few_hours';
    case HalfDay = 'half_day';
    case FullDay = 'full_day';
    case MultipleDays = 'multiple_days';

    public function label(): string
    {
        return match ($this) {
            self::FewHours => 'A few hours',
            self::HalfDay => 'Half day',
            self::FullDay => 'Full day',
            self::MultipleDays => 'Multiple days',
        };
    }
}
