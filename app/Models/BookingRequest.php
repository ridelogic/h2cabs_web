<?php

namespace App\Models;

use App\Enums\BookingType;
use App\Enums\RentalDuration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A ride request submitted through the website. It is never a confirmed
 * booking until an admin sets the status to "confirmed".
 *
 * Nothing here is mass-assignable from a customer request: the controller
 * builds every attribute from validated input and server-side quotes.
 */
class BookingRequest extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var array<string, string> */
    public const STATUSES = [
        self::STATUS_NEW => 'New request',
        self::STATUS_CONTACTED => 'Customer contacted',
        self::STATUS_CONFIRMED => 'Confirmed by H2 CABS',
        self::STATUS_COMPLETED => 'Trip completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const WHATSAPP_NOT_CONFIGURED = 'not_configured';

    public const WHATSAPP_LINK_SHOWN = 'link_shown';

    public const WHATSAPP_LINK_OPENED = 'link_opened';

    /** @var array<string, string> */
    public const WHATSAPP_STATUSES = [
        self::WHATSAPP_NOT_CONFIGURED => 'WhatsApp not configured',
        self::WHATSAPP_LINK_SHOWN => 'WhatsApp link shown',
        self::WHATSAPP_LINK_OPENED => 'WhatsApp opened by customer',
    ];

    /** Unambiguous characters for references (no 0/O, 1/I/L). */
    private const REFERENCE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /** @var list<string> */
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_type' => BookingType::class,
            'rental_duration' => RentalDuration::class,
            'sightseeing' => 'array',
            'fare_notes' => 'array',
            'travel_date' => 'date',
            'return_date' => 'date',
            'passengers' => 'integer',
            'distance_meters' => 'integer',
            'fare_amount' => 'decimal:2',
            'whatsapp_opened_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<RentalPackage, $this> */
    public function rentalPackage(): BelongsTo
    {
        return $this->belongsTo(RentalPackage::class);
    }

    /** e.g. "H2C-261009-7KQ4M" — date in IST plus 5 random characters. */
    public static function newReference(): string
    {
        $date = now(config('site.booking.timezone'))->format('ymd');

        do {
            $suffix = collect(range(1, 5))->map(fn () => self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)])->implode('');
            $reference = "H2C-{$date}-{$suffix}";
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? Str::headline((string) $this->status);
    }

    /** Badge colour for the status in the admin (see <x-admin.badge>). */
    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'amber',
            self::STATUS_CONTACTED => 'blue',
            self::STATUS_CONFIRMED, self::STATUS_COMPLETED => 'green',
            default => 'gray',
        };
    }

    public function whatsappLabel(): string
    {
        return self::WHATSAPP_STATUSES[$this->whatsapp_status] ?? Str::headline((string) $this->whatsapp_status);
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_COMPLETED], true);
    }

    /**
     * Admin search: reference, customer name or phone (digits only for phone).
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        $digits = preg_replace('/\D/', '', $term);

        $query->where(function (Builder $q) use ($like, $digits) {
            $q->where('reference', 'like', $like)
                ->orWhere('customer_name', 'like', $like);

            if (strlen($digits) >= 3) {
                $q->orWhere('customer_phone', 'like', '%'.$digits.'%');
            }
        });
    }
}
