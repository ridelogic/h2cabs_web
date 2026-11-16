<?php

namespace App\Models;

use App\Enums\BookingType;
use App\Fares\Money;
use Database\Factories\FareRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pricing for one vehicle and booking type. Monetary columns are DECIMAL
 * and cast to strings so they are never handled as floats; calculations
 * happen in App\Fares\FareCalculator.
 */
class FareRule extends Model
{
    /** @use HasFactory<FareRuleFactory> */
    use HasFactory;

    public const TOLLS_EXTRA = 'extra';

    public const TOLLS_INCLUDED = 'included';

    public const RETURN_BOTH_WAYS = 'both_ways';

    public const RETURN_ONE_WAY_ONLY = 'one_way_only';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'booking_type',
        'rate_per_km',
        'min_billable_km',
        'min_km_per_day',
        'minimum_fare',
        'driver_allowance_per_day',
        'waiting_charge_per_hour',
        'extra_hour_rate',
        'extra_charge',
        'extra_charge_label',
        'tolls_parking',
        'round_trip_distance',
        'is_approved',
        'is_active',
        'effective_from',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_type' => BookingType::class,
            'rate_per_km' => 'decimal:2',
            'min_billable_km' => 'decimal:1',
            'min_km_per_day' => 'decimal:1',
            'minimum_fare' => 'decimal:2',
            'driver_allowance_per_day' => 'decimal:2',
            'waiting_charge_per_hour' => 'decimal:2',
            'extra_hour_rate' => 'decimal:2',
            'extra_charge' => 'decimal:2',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'effective_from' => 'date',
        ];
    }

    /** Priced packages; only used by rental rules. @return HasMany<RentalPackage, $this> */
    public function packages(): HasMany
    {
        return $this->hasMany(RentalPackage::class)->orderBy('sort_order')->orderBy('hours');
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Short public price label: "₹13/km" for distance-based types, "From ₹X"
     * for rentals with packages, otherwise "Fare on request".
     */
    public function rateLabel(): string
    {
        if ($this->booking_type === BookingType::Rental) {
            $from = $this->packages->where('is_active', true)->min(fn (RentalPackage $package) => Money::toPaise($package->price));

            return $from !== null ? 'From '.Money::format($from) : 'Fare on request';
        }

        return $this->rate_per_km !== null ? Money::format(Money::toPaise($this->rate_per_km)).'/km' : 'Fare on request';
    }

    /**
     * Price label for customers. Unapproved rates are never shown publicly,
     * however they are set in the database (and unapproved fares are not
     * offered to customers at all — see bookingIssue()).
     */
    public function publicRateLabel(): string
    {
        return $this->is_approved ? $this->rateLabel() : 'Fare confirmed on booking';
    }

    /** A rule only applies once its effective date (if any) has arrived. */
    public function isInEffect(): bool
    {
        // Compare calendar dates in the booking timezone (India), exactly like
        // scopeBookable(); the app itself runs in UTC.
        return $this->is_active && ($this->effective_from === null || $this->effective_from->toDateString() <= self::today());
    }

    /**
     * Why customers cannot book with this fare, or null when they can. A
     * fare is bookable only when it is active, in effect, APPROVED and
     * complete for its booking type. Mirrors scopeBookable().
     */
    public function bookingIssue(): ?string
    {
        return match (true) {
            ! $this->is_active => 'Fare is switched off',
            ! $this->isInEffect() => 'Starts '.$this->effective_from->format('j M Y'),
            ! $this->is_approved => 'Not approved yet',
            $this->booking_type === BookingType::Rental && ! $this->packages->contains('is_active', true) => 'No active rental package',
            $this->booking_type->isDistanceBased() && $this->rate_per_km === null => 'No rate per km',
            $this->booking_type === BookingType::OutstationRoundTrip && ! in_array($this->round_trip_distance, [self::RETURN_BOTH_WAYS, self::RETURN_ONE_WAY_ONLY], true) => 'Return billing not set',
            default => null,
        };
    }

    public function isBookable(): bool
    {
        return $this->bookingIssue() === null;
    }

    /**
     * Fares customers can book for a booking type (same rules as bookingIssue()).
     *
     * @param  Builder<FareRule>  $query
     */
    public function scopeBookable(Builder $query, BookingType $type): void
    {
        $query->where('booking_type', $type->value)
            ->where('is_active', true)
            ->where('is_approved', true)
            ->where(fn (Builder $q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', self::today()))
            ->when($type->isDistanceBased(), fn (Builder $q) => $q->whereNotNull('rate_per_km'))
            ->when($type === BookingType::OutstationRoundTrip, fn (Builder $q) => $q->whereIn('round_trip_distance', [self::RETURN_BOTH_WAYS, self::RETURN_ONE_WAY_ONLY]))
            ->when($type === BookingType::Rental, fn (Builder $q) => $q->whereHas('packages', fn (Builder $packages) => $packages->where('is_active', true)));
    }

    private static function today(): string
    {
        return now(config('site.booking.timezone'))->toDateString();
    }
}
