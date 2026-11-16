<?php

namespace App\Http\Requests\Admin;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('vehicles', 'slug')->ignore($vehicle)],
            'category' => ['required', Rule::in(array_keys(Vehicle::CATEGORIES))],
            'passenger_capacity' => ['nullable', 'integer', 'min:1', 'max:60'],
            'luggage_capacity' => ['nullable', 'integer', 'min:0', 'max:60'],
            'description' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['boolean'],
        ];
    }
}
