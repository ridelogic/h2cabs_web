<?php

namespace App\Models;

use App\Fares\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A priced rental package (e.g. "4 hrs / 40 km") for one vehicle's rental
 * fare rule. Usage beyond the package is billed at the rule's extra-km and
 * extra-hour rates, when configured.
 */
class RentalPackage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'hours', 'included_km', 'price', 'is_active', 'sort_order'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hours' => 'decimal:1',
            'included_km' => 'decimal:1',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<FareRule, $this> */
    public function fareRule(): BelongsTo
    {
        return $this->belongsTo(FareRule::class);
    }

    /**
     * e.g. "4 hours (4 hrs / 40 km) — ₹1,200". The price is included only
     * when asked for; customers see it only once the rental rule is approved.
     */
    public function summary(bool $withPrice = true): string
    {
        $parts = [Money::formatKm(Money::toTenths($this->hours)).' hrs'];

        if ($this->included_km !== null) {
            $parts[] = Money::formatKm(Money::toTenths($this->included_km)).' km';
        }

        $label = $this->name.' ('.implode(' / ', $parts).')';

        return $withPrice ? $label.' — '.Money::format(Money::toPaise($this->price)) : $label;
    }

    /** Customer-facing label: the price only when the rental rate is approved. */
    public function publicSummary(): string
    {
        return $this->summary((bool) $this->fareRule?->is_approved);
    }
}
