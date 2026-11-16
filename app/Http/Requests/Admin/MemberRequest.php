<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_published' => $this->boolean('is_published'),
            'remove_photo' => $this->boolean('remove_photo'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:80'],
            'member_group' => ['required', Rule::in(array_keys(Member::GROUPS))],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[+()\d\s-]{7,20}$/'],
            'bio' => ['nullable', 'string', 'max:500'],
            'member_since' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'is_published' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=120,min_height=120,max_width=4000,max_height=4000'],
            'remove_photo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number using digits, spaces, + or -.',
            'photo.dimensions' => 'The photo must be between 120×120 and 4000×4000 pixels.',
        ];
    }
}
