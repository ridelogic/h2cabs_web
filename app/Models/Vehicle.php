<?php

namespace App\Models;

use App\Enums\BookingType;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A bookable vehicle category (Sedan, SUV, ...). Capacities stay null
 * unless the association has verified them.
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public const CATEGORIES = [
        'mini' => 'Mini',
        'hatchback' => 'Hatchback',
        'sedan' => 'Sedan',
        'suv' => 'SUV',
        'muv' => 'MUV / MPV',
        'premium' => 'Premium',
        'van' => 'Tempo Traveller / Van',
        'large' => 'Larger passenger vehicle',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'category',
        'image_path',
        'passenger_capacity',
        'luggage_capacity',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'passenger_capacity' => 'integer',
            'luggage_capacity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<FareRule, $this> */
    public function fareRules(): HasMany
    {
        return $this->hasMany(FareRule::class);
    }

    /** @param  Builder<Vehicle>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /** Active vehicles with a bookable (active, approved, complete) fare for the booking type. */
    public function scopeBookableFor(Builder $query, BookingType $type): void
    {
        $query->active()->whereHas('fareRules', fn (Builder $rules) => $rules->bookable($type));
    }

    /** The fare customers can book for this type, or null if it is not offered. */
    public function fareFor(BookingType $type): ?FareRule
    {
        return $this->fareRules->first(fn (FareRule $rule) => $rule->booking_type === $type && $rule->isBookable());
    }

    /** The configured fare for a type, bookable or not (admin views). */
    public function configuredFareFor(BookingType $type): ?FareRule
    {
        return $this->fareRules->first(fn (FareRule $rule) => $rule->booking_type === $type);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
