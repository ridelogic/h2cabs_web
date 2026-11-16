<?php

namespace App\Fares;

/**
 * Result of a fare calculation. Every amount is integer paise.
 *
 * The raw numbers (total, lines) are always calculated so the admin preview
 * can show them. What CUSTOMERS see comes only from status(), headline(),
 * summaryLine() and toArray(), which never reveal an amount unless the
 * fare rule is approved AND a full estimate could be calculated:
 *
 *   not approved                  → "Fare will be confirmed by H2 CABS."
 *   approved, route not verified  → "Fare will be confirmed after the route is verified."
 *   approved, estimate available  → "Estimated fare: ₹X — not a confirmed quote"
 */
final class FareEstimate
{
    public const ESTIMATED = 'estimated';

    public const REQUIRES_CONFIRMATION = 'requires_confirmation';

    public const REASON_UNAPPROVED = 'unapproved';

    public const REASON_NO_RATE = 'no_rate';

    public const REASON_NO_DISTANCE = 'no_distance';

    public const REASON_NO_PACKAGE = 'no_package';

    public const REASON_NO_RETURN_POLICY = 'no_return_policy';

    /**
     * @param  list<array{label: string, paise: int}>  $lines
     * @param  list<string>  $notes  Conditions such as tolls or waiting (shown only for approved fares).
     * @param  list<string>  $details  Configured pricing facts such as the rate (shown only for approved fares).
     */
    public function __construct(
        public readonly ?int $ratePaise,
        public readonly ?int $tripKmTenths,
        public readonly ?int $billableKmTenths,
        public readonly array $lines,
        public readonly ?int $total,
        public readonly ?int $minimum,
        public readonly bool $approved,
        public readonly array $notes,
        public readonly ?string $reason = null,
        public readonly array $details = [],
    ) {}

    public function hasTotal(): bool
    {
        return $this->total !== null;
    }

    /** "estimated" only for an approved fare with a complete calculation. */
    public function status(): string
    {
        return $this->approved && $this->total !== null ? self::ESTIMATED : self::REQUIRES_CONFIRMATION;
    }

    public function isEstimated(): bool
    {
        return $this->status() === self::ESTIMATED;
    }

    /** Why the fare needs confirmation, or null when it is estimated. */
    public function confirmationReason(): ?string
    {
        if ($this->isEstimated()) {
            return null;
        }

        return $this->approved ? ($this->reason ?? self::REASON_NO_RATE) : self::REASON_UNAPPROVED;
    }

    /** The customer-facing message for the fare panel and review page. */
    public function headline(): string
    {
        return match ($this->confirmationReason()) {
            null => 'Estimated fare: '.Money::format($this->total).' — not a confirmed quote',
            self::REASON_NO_DISTANCE => 'Fare will be confirmed after the route is verified.',
            self::REASON_NO_PACKAGE => 'Choose a rental package to see the estimate.',
            default => 'Fare will be confirmed by H2 CABS.',
        };
    }

    /** One line for the WhatsApp message and booking record. */
    public function summaryLine(): string
    {
        return $this->isEstimated()
            ? Money::format($this->total).' (estimated fare — not a confirmed quote)'
            : rtrim($this->headline(), '.');
    }

    /**
     * Customer-safe representation (estimate API, review page).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $estimated = $this->isEstimated();

        return [
            'status' => $this->status(),
            'reason' => $this->confirmationReason(),
            'headline' => $this->headline(),
            'summary' => $this->summaryLine(),
            'total' => $estimated ? Money::format($this->total) : null,
            'lines' => $estimated ? array_map(fn (array $line) => ['label' => $line['label'], 'amount' => Money::format($line['paise'])], $this->lines) : [],
            'billable_km' => $estimated && $this->billableKmTenths !== null ? Money::formatKm($this->billableKmTenths) : null,
            'details' => $this->approved ? $this->details : [],
            'notes' => $this->approved ? $this->notes : [],
        ];
    }
}
