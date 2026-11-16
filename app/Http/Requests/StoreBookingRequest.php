<?php

namespace App\Http\Requests;

use App\Content\DestinationCatalog;
use App\Enums\BookingType;
use App\Models\Vehicle;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Booking request validation. Only the fields listed here are accepted:
 * fare, distance or price values posted by the browser are ignored and
 * always recalculated on the server (see App\Booking\BookingQuoter).
 */
class StoreBookingRequest extends FormRequest
{
    private const PLACE_ID = 'regex:/^[A-Za-z0-9_-]{10,300}$/';

    /** Maximum span of a round trip, in days. */
    public const MAX_TRIP_DAYS = 30;

    /**
     * The homepage widget and the booking page share this form; errors are
     * always shown on the full booking page, with the input preserved.
     */
    protected $redirectRoute = 'booking.create';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trimmed = collect(['pickup', 'dropoff', 'name', 'phone', 'notes'])
            ->filter(fn (string $key) => is_string($this->input($key)))
            ->mapWithKeys(fn (string $key) => [$key => trim($this->input($key))]);

        // Spaces typed or pasted into the mobile number are not significant.
        if (is_string($this->input('phone'))) {
            $trimmed['phone'] = preg_replace('/\s+/', '', $this->input('phone'));
        }

        $this->merge($trimmed->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $today = $this->today()->toDateString();

        return [
            'form_token' => ['nullable', 'uuid'],
            'booking_type' => ['required', Rule::enum(BookingType::class)],
            'vehicle_id' => ['required', 'integer', $this->bookableVehicle(...)],

            // Rental: one of the vehicle's approved packages (checked in after()).
            'rental_package_id' => ['exclude_unless:booking_type,rental', 'nullable', 'integer'],
            'sightseeing' => ['exclude_unless:booking_type,rental', 'nullable', 'array', 'max:10'],
            'sightseeing.*' => ['string', Rule::in(app(DestinationCatalog::class)->slugs())],

            'pickup' => ['required', 'string', 'min:3', 'max:200'],
            // Locations must be chosen from Google suggestions (no manual entry).
            'pickup_place_id' => ['required', 'string', self::PLACE_ID],
            'dropoff' => ['required_unless:booking_type,rental', 'nullable', 'string', 'min:3', 'max:200'],
            'dropoff_place_id' => ['nullable', 'required_with:dropoff', 'string', self::PLACE_ID],

            'travel_date' => ['required', 'date_format:Y-m-d', "after_or_equal:{$today}", 'before_or_equal:'.$this->today()->addYear()->toDateString()],
            'pickup_time' => ['required', 'date_format:H:i'],
            'return_date' => ['exclude_unless:booking_type,outstation_round_trip', 'required', 'date_format:Y-m-d', 'after_or_equal:travel_date'],
            'return_time' => ['exclude_unless:booking_type,outstation_round_trip', 'nullable', 'date_format:H:i'],

            'passengers' => ['required', 'integer', 'min:1', 'max:'.config('site.booking.max_passengers')],
            'name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\pM\s.\'-]+$/u'],
            // Exactly 10 digits, stored as typed (no country code is added).
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'booking_type.required' => 'Please choose a booking type.',
            'vehicle_id.required' => 'Please choose a vehicle.',
            'pickup.required' => 'Please enter a pickup location.',
            'pickup.min' => 'Please enter a fuller pickup location.',
            'dropoff.required_unless' => 'Please enter the destination.',
            'dropoff.min' => 'Please enter a fuller destination.',
            'travel_date.required' => 'Please choose a travel date.',
            'travel_date.after_or_equal' => 'The travel date cannot be in the past.',
            'travel_date.before_or_equal' => 'Bookings can be requested up to a year ahead.',
            'travel_date.date_format' => 'Please choose a valid travel date.',
            'pickup_time.required' => 'Please choose a pickup time.',
            'pickup_time.date_format' => 'Please choose a valid pickup time.',
            'return_date.required' => 'Please choose a return date for the round trip.',
            'return_date.after_or_equal' => 'The return date cannot be earlier than the departure date.',
            'return_date.date_format' => 'Please choose a valid return date.',
            'passengers.max' => 'For more than :max passengers, please contact us directly.',
            'name.required' => 'Please enter your name.',
            'name.regex' => 'Please enter your name using letters only.',
            'phone.required' => 'Please enter your mobile number.',
            'phone.regex' => 'Please enter a 10-digit mobile number, using digits only (no +91 or 0 prefix).',
            'pickup_place_id.required' => 'Please choose the pickup location from the Google suggestions.',
            'pickup_place_id.regex' => 'Please choose the pickup location again.',
            'dropoff_place_id.required_with' => 'Please choose the destination from the Google suggestions.',
            'dropoff_place_id.regex' => 'Please choose the destination again.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'vehicle_id' => 'vehicle',
            'dropoff' => 'destination',
            'travel_date' => 'travel date',
            'pickup_time' => 'pickup time',
            'return_date' => 'return date',
            'sightseeing.*' => 'destination',
            'phone' => 'phone number',
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $errors = $validator->errors();

                // A same-day pickup must still be ahead of the current local time.
                if (! $errors->hasAny(['travel_date', 'pickup_time'])) {
                    $pickup = Carbon::createFromFormat('Y-m-d H:i', $this->input('travel_date').' '.$this->input('pickup_time'), config('site.booking.timezone'));

                    if ($pickup->isPast()) {
                        $errors->add('pickup_time', 'The pickup time has already passed. Please choose a later time.');
                    }
                }

                if ($this->input('booking_type') === BookingType::OutstationRoundTrip->value && ! $errors->hasAny(['travel_date', 'return_date', 'pickup_time', 'return_time'])) {
                    // (Carbon 3 returns a float here, so cast before comparing.)
                    $span = (int) Carbon::parse($this->input('travel_date'))->diffInDays(Carbon::parse($this->input('return_date')));

                    if ($span >= self::MAX_TRIP_DAYS) {
                        $errors->add('return_date', 'For trips longer than '.self::MAX_TRIP_DAYS.' days, please contact us directly.');
                    }

                    // Same-day return: the return time must be after the departure time.
                    if ($span === 0 && filled($this->input('return_time')) && $this->input('return_time') <= $this->input('pickup_time')) {
                        $errors->add('return_time', 'The return time must be later than the departure time.');
                    }
                }

                if ($this->input('booking_type') === BookingType::Rental->value && ! $errors->has('vehicle_id')) {
                    $this->validateRental($errors);
                }

                // Respect a vehicle's verified capacity, when one is set.
                if (! $errors->hasAny(['vehicle_id', 'passengers'])) {
                    $capacity = Vehicle::find($this->input('vehicle_id'))?->passenger_capacity;

                    if ($capacity && (int) $this->input('passengers') > $capacity) {
                        $errors->add('passengers', "This vehicle seats up to {$capacity} passengers. Please choose a larger vehicle.");
                    }
                }
            },
        ];
    }

    /**
     * A rental is bookable only through one of the vehicle's active
     * packages on its approved rental fare.
     */
    private function validateRental(MessageBag $errors): void
    {
        $packages = Vehicle::find($this->input('vehicle_id'))?->fareFor(BookingType::Rental)?->packages->where('is_active', true) ?? collect();

        if (! $packages->contains('id', (int) $this->input('rental_package_id'))) {
            $errors->add('rental_package_id', 'Please choose a rental package.');
        }
    }

    private function bookableVehicle(string $attribute, mixed $value, Closure $fail): void
    {
        $type = BookingType::tryFrom((string) $this->input('booking_type'));

        if (! $type || ! Vehicle::query()->bookableFor($type)->whereKey($value)->exists()) {
            $fail('Please choose one of the available vehicles for this trip.');
        }
    }

    private function today(): Carbon
    {
        return Carbon::now(config('site.booking.timezone'))->startOfDay();
    }
}
