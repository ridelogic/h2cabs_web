<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RentalPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'price' => is_string($this->input('price')) ? trim(str_replace([',', '₹'], '', $this->input('price'))) : $this->input('price'),
            'included_km' => $this->input('included_km') === '' ? null : $this->input('included_km'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'hours' => ['required', 'numeric', 'decimal:0,1', 'min:0.5', 'max:240'],
            'included_km' => ['nullable', 'numeric', 'decimal:0,1', 'min:0', 'max:5000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
        ];
    }
}
