<x-layouts.admin :title="$page->exists ? 'Edit page' : 'Add new page'" :subtitle="$page->exists ? $page->title : 'Create a page for the public website.'">
    <x-slot:actions>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline"><x-ui.icon name="arrow-right" class="size-4 rotate-180" /> All pages</a>
        @if ($page->exists)
            <a href="{{ $page->is_published ? $page->url() : route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="btn btn-outline">
                <x-ui.icon name="eye" class="size-4" /> {{ $page->is_published ? 'View page' : 'Preview draft' }}
            </a>
        @endif
    </x-slot:actions>

    <form method="POST" enctype="multipart/form-data" class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]"
        action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        <div class="space-y-6">
            <section class="admin-panel space-y-5 p-5 sm:p-6" aria-labelledby="page-content-title">
                <h2 id="page-content-title" class="admin-panel-title"><x-ui.icon name="file-text" class="size-5 text-admin-green" /> Page content</h2>

                <x-admin.field name="title" label="Page title" :value="$page->title" required maxlength="120" data-slug-source />

                <div>
                    <x-admin.field name="slug" label="URL slug" :value="$page->slug" prefix="/" maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                        data-slug-target :data-slug-auto="$page->exists ? 'false' : 'true'"
                        help="Filled in from the title. Lowercase letters, numbers and hyphens only. Paths already used by the website (booking, contact, admin, …) are not allowed." />
                    <p class="mt-1.5 text-xs break-all text-slate-500">Web address: <span class="font-mono text-slate-700">{{ url('/') }}/<span data-slug-preview>{{ old('slug', $page->slug) ?: 'your-page' }}</span></span></p>
                </div>

                <div>
                    <label for="admin-content" class="form-label">Content <span class="text-red-600" aria-hidden="true">*</span></label>
                    <textarea id="admin-content" name="content" rows="18" required maxlength="100000" aria-describedby="admin-content-help"
                        class="form-control font-mono text-sm leading-relaxed" @error('content') aria-invalid="true" @enderror>{{ old('content', $page->content) }}</textarea>
                    <div id="admin-content-help" class="form-help">
                        Plain text with simple formatting: <code>## Heading</code>, <code>**bold**</code>, <code>*italic*</code>,
                        <code>- list item</code>, <code>[link text](https://example.com)</code>. Leave a blank line between paragraphs.
                        HTML code is removed for security.
                    </div>
                    <x-form.field-error name="content" id="admin-content" />
                </div>
            </section>

            <section class="admin-panel space-y-5 p-5 sm:p-6" aria-labelledby="page-seo-title">
                <div>
                    <h2 id="page-seo-title" class="admin-panel-title"><x-ui.icon name="search" class="size-5 text-admin-green" /> Search engines</h2>
                    <p class="admin-panel-subtitle">Optional. When blank, the page title and the start of the content are used.</p>
                </div>
                <x-admin.field name="meta_title" label="Meta title" :value="$page->meta_title" maxlength="70" help="Up to 70 characters. “| H2 CABS” is added automatically." />
                <div>
                    <label for="admin-meta_description" class="form-label">Meta description</label>
                    <textarea id="admin-meta_description" name="meta_description" rows="3" maxlength="300" aria-describedby="admin-meta_description-help" class="form-control">{{ old('meta_description', $page->meta_description) }}</textarea>
                    <p id="admin-meta_description-help" class="form-help">Up to 300 characters; around 155 shows in search results.</p>
                    <x-form.field-error name="meta_description" id="admin-meta_description" />
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="admin-panel space-y-5 p-5 sm:p-6" aria-labelledby="page-publish-title">
                <h2 id="page-publish-title" class="admin-panel-title"><x-ui.icon name="globe" class="size-5 text-admin-green" /> Publishing</h2>
                <p>@if ($page->is_published)<x-admin.badge tone="green">Published</x-admin.badge>@else<x-admin.badge tone="amber">Draft</x-admin.badge>@endif</p>
                <x-admin.switch name="is_published" label="Published" :checked="$page->is_published" help="Drafts are only visible to admins via Preview." />

                <div>
                    <label for="admin-page_type" class="form-label">Page type</label>
                    <select id="admin-page_type" name="page_type" class="form-control">
                        @foreach (\App\Models\Page::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('page_type', $page->page_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-form.field-error name="page_type" id="admin-page_type" />
                </div>

                <div>
                    <label for="admin-nav_placement" class="form-label">Link to this page from</label>
                    <select id="admin-nav_placement" name="nav_placement" class="form-control" aria-describedby="admin-nav_placement-help">
                        @foreach (\App\Models\Page::PLACEMENTS as $value => $label)
                            <option value="{{ $value }}" @selected(old('nav_placement', $page->nav_placement) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p id="admin-nav_placement-help" class="form-help">Adds a link after the existing ones while the page is published. Keep the main navigation short.</p>
                    <x-form.field-error name="nav_placement" id="admin-nav_placement" />
                </div>
                <x-admin.field name="nav_order" label="Link order" :value="$page->nav_order" type="number" min="0" max="1000" help="Lower numbers come first." />

                <div class="flex flex-wrap gap-3 border-t border-admin-line pt-5">
                    <button type="submit" class="btn btn-primary flex-1">{{ $page->exists ? 'Save changes' : 'Create page' }}</button>
                </div>
            </section>

            <section class="admin-panel space-y-4 p-5 sm:p-6" aria-labelledby="page-image-title">
                <h2 id="page-image-title" class="admin-panel-title"><x-ui.icon name="camera" class="size-5 text-admin-green" /> Featured image</h2>
                @if ($page->imageUrl())
                    <img src="{{ $page->imageUrl() }}" alt="Current featured image" class="aspect-[2/1] w-full rounded-xl object-cover">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1" class="size-4 accent-red-600"> Remove image</label>
                @endif
                <input type="file" id="admin-featured_image" name="featured_image" accept="image/jpeg,image/png,image/webp" aria-describedby="admin-featured_image-help"
                    class="block w-full text-sm file:mr-4 file:rounded-full file:border-0 file:bg-brand-blue-50 file:px-4 file:py-2 file:font-semibold file:text-brand-blue-800">
                <p id="admin-featured_image-help" class="form-help">Optional. JPG, PNG or WebP, up to 4 MB, at least 600×300 px. Shown at the top of the page and when shared.</p>
                <x-form.field-error name="featured_image" id="admin-featured_image" />
            </section>
        </aside>
    </form>

    @if ($page->exists)
        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="admin-panel mt-6 flex flex-col gap-4 border-red-200 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"
            data-confirm="Delete “{{ $page->title }}”? Its web address will stop working. This cannot be undone." onsubmit="return confirm(this.dataset.confirm)">
            @csrf @method('DELETE')
            <div>
                <h2 class="font-bold text-red-800">Delete page</h2>
                <p class="mt-1 text-sm text-slate-500">To take the page offline but keep it, unpublish it instead.</p>
            </div>
            <button type="submit" class="btn shrink-0 border border-red-300 text-red-700 hover:bg-red-50"><x-ui.icon name="trash" class="size-4" /> Delete page</button>
        </form>
    @endif
</x-layouts.admin>
