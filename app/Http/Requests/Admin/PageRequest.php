<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        // A blank slug is generated from the title; a typed one is normalised.
        $slug = trim((string) $this->input('slug'));

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : (string) $this->input('title')),
            'is_published' => $this->boolean('is_published'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => [
                'required', 'string', 'max:80', 'regex:/^'.Page::SLUG_PATTERN.'$/',
                Rule::unique('pages', 'slug')->ignore($page?->getKey()),
                Rule::notIn(Page::reservedSlugs()),
            ],
            'page_type' => ['required', Rule::in(array_keys(Page::TYPES))],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'content' => ['required', 'string', 'min:10', 'max:100000'],
            'is_published' => ['boolean'],
            'nav_placement' => ['required', Rule::in(array_keys(Page::PLACEMENTS))],
            'nav_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,min_height=300,max_width=6000,max_height=6000'],
            'remove_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens only, e.g. airport-transfer.',
            'slug.unique' => 'Another page already uses this URL. Choose a different slug.',
            'slug.not_in' => 'This URL is used by an existing part of the website. Choose a different slug.',
            'meta_title.max' => 'Keep the meta title under 70 characters so search results do not cut it off.',
            'featured_image.dimensions' => 'The image must be at least 600×300 pixels (and at most 6000×6000).',
        ];
    }

    public function attributes(): array
    {
        return ['slug' => 'URL slug', 'featured_image' => 'featured image', 'nav_placement' => 'link placement'];
    }
}
