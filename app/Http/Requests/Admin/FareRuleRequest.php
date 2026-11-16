<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingType;
use App\Models\FareRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Fare values: non-negative, rupees with at most 2 decimals, kilometres
 * with at most 1 decimal. Stored as DECIMAL; never cast to float.
 */
class FareRuleRequest extends FormRequest
{
    private const MONEY = ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'];

    private const KM = ['nullable', 'numeric', 'decimal:0,1', 'min:0', 'max:10000'];

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $trimmed = collect(['rate_per_km', 'min_billable_km', 'min_km_per_day', 'minimum_fare', 'driver_allowance_per_day', 'waiting_charge_per_hour', 'extra_hour_rate', 'extra_charge'])
            ->mapWithKeys(fn (string $key) => [$key => is_string($this->input($key)) ? trim(str_replace([',', '₹'], '', $this->input($key))) : $this->input($key)])
            ->map(fn ($value) => $value === '' ? null : $value);

        $this->merge([
            ...$trimmed->all(),
            'is_approved' => $this->boolean('is_approved'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fare = $this->route('fare');
        $type = $fare?->booking_type?->value ?? $this->input('booking_type');

        return [
            // Only settable when creating; a rule's vehicle and type never change.
            'vehicle_id' => [Rule::requiredIf(! $fare), Rule::prohibitedIf((bool) $fare), 'integer', 'exists:vehicles,id'],
            'booking_type' => [Rule::requiredIf(! $fare), Rule::prohibitedIf((bool) $fare), Rule::enum(BookingType::class),
                Rule::unique('fare_rules')->where('vehicle_id', $this->input('vehicle_id'))],

            // Rentals are priced by package; their per-km rate is an optional extra-km rate.
            'rate_per_km' => $type === BookingType::Rental->value ? self::MONEY : ['required', ...array_slice(self::MONEY, 1)],
            'min_billable_km' => self::KM,
            'min_km_per_day' => self::KM,
            'minimum_fare' => self::MONEY,
            'driver_allowance_per_day' => self::MONEY,
            'waiting_charge_per_hour' => self::MONEY,
            'extra_hour_rate' => self::MONEY,
            'extra_charge' => self::MONEY,
            'extra_charge_label' => ['nullable', 'required_with:extra_charge', 'string', 'max:80'],
            'tolls_parking' => ['required', Rule::in([FareRule::TOLLS_EXTRA, FareRule::TOLLS_INCLUDED])],
            'round_trip_distance' => $type === BookingType::OutstationRoundTrip->value
                ? ['required', Rule::in([FareRule::RETURN_BOTH_WAYS, FareRule::RETURN_ONE_WAY_ONLY])]
                : ['exclude'],
            'is_approved' => ['boolean'],
            'is_active' => ['boolean'],
            'effective_from' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'booking_type.unique' => 'This vehicle already has a fare for that booking type. Edit the existing fare instead.',
            '*.decimal' => 'Use at most :decimal decimal places.',
            'round_trip_distance.required' => 'Choose how the return journey is billed.',
            'extra_charge_label.required_with' => 'Describe the additional charge.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rate_per_km' => 'rate per km',
            'min_billable_km' => 'minimum billable km',
            'min_km_per_day' => 'minimum km per day',
            'minimum_fare' => 'minimum fare',
            'driver_allowance_per_day' => 'driver allowance',
            'waiting_charge_per_hour' => 'waiting charge',
            'extra_charge' => 'additional charge',
        ];
    }
}
