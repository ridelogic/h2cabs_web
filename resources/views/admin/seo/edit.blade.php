<x-layouts.admin :title="'SEO — '.$label">
    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <form method="POST" action="{{ route('admin.seo.update', $route) }}" class="card space-y-6 p-6 sm:p-8">
            @csrf @method('PUT')

            <div>
                <label for="seo-title" class="form-label">Page title</label>
                <input id="seo-title" name="title" value="{{ old('title', $record?->title) }}" maxlength="70" placeholder="{{ $defaultTitle }}" class="form-control"
                    aria-describedby="seo-title-help" @error('title') aria-invalid="true" @enderror>
                <p id="seo-title-help" class="form-help">Used exactly as written. Leave blank for the default: “{{ $defaultTitle }}”. Aim for under 60 characters.</p>
                <x-form.field-error name="title" id="seo-title" />
            </div>

            <div>
                <label for="seo-description" class="form-label">Meta description</label>
                <textarea id="seo-description" name="description" rows="3" maxlength="300" placeholder="{{ $defaultDescription }}" class="form-control"
                    aria-describedby="seo-description-help" @error('description') aria-invalid="true" @enderror>{{ old('description', $record?->description) }}</textarea>
                <p id="seo-description-help" class="form-help">About 150–160 characters works best. Describe the page honestly — no keyword stuffing. Leave blank for the default.</p>
                <x-form.field-error name="description" id="seo-description" />
            </div>

            <x-admin.switch name="indexable" label="Allow search engines to index this page" :checked="$record?->indexable ?? true"
                help="Turn off to add “noindex” and remove the page from the sitemap." />

            <div class="flex flex-wrap gap-3 border-t border-line pt-6">
                <button type="submit" class="btn btn-primary">Save SEO</button>
                <a href="{{ route('admin.seo.index') }}" class="btn btn-outline">Back to SEO</a>
            </div>
        </form>

        <aside aria-labelledby="preview-title">
            <div class="card p-6">
                <h2 id="preview-title" class="text-sm font-bold tracking-wide text-muted uppercase">Search result preview (saved)</h2>
                <div class="mt-4 rounded-xl border border-line p-4">
                    <p class="truncate text-xs text-muted">{{ $url }}</p>
                    <p class="mt-1 text-lg leading-snug text-[#1a0dab]">{{ \Illuminate\Support\Str::limit($seo->title, 60) }}</p>
                    <p class="mt-1 text-sm text-ink">{{ \Illuminate\Support\Str::limit($seo->description, 160) }}</p>
                </div>
                <p class="mt-3 text-xs text-muted">Search engines decide how results look; this is an approximation.</p>
            </div>
        </aside>
    </div>
</x-layouts.admin>
