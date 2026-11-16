<x-layouts.admin title="Website settings">
    <p class="max-w-3xl text-muted">
        These values appear on the public website as soon as you save. Leave a field blank to use the server default
        from <code>.env</code> (shown below each field). Only enter details the business has confirmed.
    </p>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf @method('PUT')

        @foreach ($groups as $group => $fields)
            <fieldset class="card p-6 sm:p-8">
                <legend class="sr-only">{{ $group }}</legend>
                <h2 class="text-lg font-bold text-brand-blue-950">{{ $group }}</h2>
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    @foreach ($fields as $field)
                        <div>
                            <label for="setting-{{ $field['field'] }}" class="form-label">{{ $field['label'] }}</label>
                            <input id="setting-{{ $field['field'] }}" name="{{ $field['field'] }}" type="{{ $field['type'] }}"
                                value="{{ old($field['field'], $field['saved']) }}" class="form-control"
                                @if ($field['type'] === 'url') placeholder="https://…" inputmode="url" @endif
                                @error($field['field']) aria-invalid="true" @enderror
                                aria-describedby="setting-{{ $field['field'] }}-help">
                            <p id="setting-{{ $field['field'] }}-help" class="form-help">
                                @if ($field['help']){{ $field['help'] }} @endif
                                @if ($field['saved'])
                                    <x-admin.badge tone="blue">Set in dashboard</x-admin.badge>
                                @elseif ($field['default'])
                                    <span>Using server default: <span class="break-all font-medium text-ink">{{ $field['default'] }}</span></span>
                                @else
                                    <x-admin.badge>Not set</x-admin.badge>
                                @endif
                            </p>
                            <x-form.field-error :name="$field['field']" :id="'setting-'.$field['field']" />
                        </div>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <fieldset class="card p-6 sm:p-8">
            <legend class="sr-only">Branding</legend>
            <h2 class="text-lg font-bold text-brand-blue-950">Branding — logo mark</h2>
            <p class="mt-1 text-sm text-muted">The square mark shown beside "H2 CABS" in the header, footer and browser tab. PNG, JPG or WebP, square, at least 64×64 px, up to 1 MB. SVG uploads are not accepted for security.</p>
            <div class="mt-5 flex flex-wrap items-center gap-6">
                <img src="{{ asset(config('site.brand.logo_mark')) }}" alt="Current logo mark" class="size-20 rounded-2xl bg-surface object-contain p-1 ring-1 ring-line">
                <div class="space-y-3">
                    <input type="file" id="setting-logo" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm file:mr-4 file:rounded-full file:border-0 file:bg-brand-blue-50 file:px-4 file:py-2 file:font-semibold file:text-brand-blue-800">
                    @if ($customLogo)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_logo" value="1" class="size-4 accent-red-600"> Remove uploaded logo (use the default)</label>
                    @endif
                    <x-form.field-error name="logo" id="setting-logo" />
                </div>
            </div>
            @unless ($storageLinked)
                <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    Run <code>php artisan storage:link</code> on the server before uploading, otherwise the uploaded logo cannot be displayed.
                </p>
            @endunless
        </fieldset>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">Save settings</button>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-outline">View website</a>
        </div>
    </form>
</x-layouts.admin>
