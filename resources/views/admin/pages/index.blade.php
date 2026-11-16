<x-layouts.admin title="Website Pages" subtitle="Create extra pages for the public website, such as policies or service landing pages.">
    <x-slot:actions>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><x-ui.icon name="plus" class="size-4" /> Add New Page</a>
    </x-slot:actions>

    @if ($pages->isEmpty())
        <div class="admin-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="relative flex size-24 items-center justify-center" aria-hidden="true">
                <span class="absolute inset-0 rounded-full bg-admin-accent"></span>
                <span class="absolute inset-3 rounded-full bg-white shadow-sm"></span>
                <x-ui.icon name="file-text" class="relative size-10 text-brand-blue-500" />
            </span>
            <h2 class="mt-5 text-lg font-bold text-admin-navy">No custom pages yet</h2>
            <p class="mt-1 max-w-md text-sm text-slate-500">
                Add pages such as “Corporate Travel”, “Airport Transfer” or a cancellation policy. Each page gets its own web address,
                and you choose when it goes live. The built-in pages (Home, About, Services, Booking, Contact) are not affected.
            </p>
            <a href="{{ route('admin.pages.create') }}" class="btn btn-primary mt-6"><x-ui.icon name="plus" class="size-4" /> Create your first page</a>
        </div>
    @else
        <div class="admin-panel overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <caption class="sr-only">Custom website pages</caption>
                <thead class="border-b border-admin-line text-xs uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3">Page title</th>
                        <th scope="col" class="px-5 py-3">URL slug</th>
                        <th scope="col" class="px-5 py-3">Type</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Last updated</th>
                        <th scope="col" class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-admin-line">
                    @foreach ($pages as $page)
                        <tr>
                            <th scope="row" class="min-w-48 px-5 py-4 align-top">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold text-admin-navy hover:underline">{{ $page->title }}</a>
                                @if ($page->nav_placement !== 'none')
                                    <span class="mt-1 flex items-center gap-1 text-xs font-normal text-slate-500"><x-ui.icon name="menu" class="size-3.5" /> Linked in {{ strtolower(\App\Models\Page::PLACEMENTS[$page->nav_placement]) }}</span>
                                @endif
                            </th>
                            <td class="px-5 py-4 align-top whitespace-nowrap"><code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700">/{{ $page->slug }}</code></td>
                            <td class="px-5 py-4 align-top whitespace-nowrap text-slate-600">{{ $page->typeLabel() }}</td>
                            <td class="px-5 py-4 align-top">
                                <form method="POST" action="{{ route('admin.pages.toggle', $page) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" title="Click to {{ $page->is_published ? 'unpublish' : 'publish' }}" aria-label="{{ $page->is_published ? 'Unpublish' : 'Publish' }} {{ $page->title }}">
                                        @if ($page->is_published)<x-admin.badge tone="green">Published</x-admin.badge>@else<x-admin.badge tone="amber">Draft</x-admin.badge>@endif
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-4 align-top whitespace-nowrap text-slate-600">
                                <time datetime="{{ $page->updated_at->toIso8601String() }}">{{ $page->updated_at->timezone(config('site.booking.timezone'))->format('j M Y, g:i A') }}</time>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.pages.edit', $page) }}" class="admin-icon-btn size-9" title="Edit">
                                        <x-ui.icon name="edit" class="size-4" /><span class="sr-only">Edit {{ $page->title }}</span>
                                    </a>
                                    <a href="{{ $page->is_published ? $page->url() : route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="admin-icon-btn size-9" title="{{ $page->is_published ? 'View on website' : 'Preview draft' }}">
                                        <x-ui.icon name="eye" class="size-4" /><span class="sr-only">Preview {{ $page->title }} (opens in a new tab)</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                        data-confirm="Delete “{{ $page->title }}”? Its web address will stop working. This cannot be undone." onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="admin-icon-btn size-9 hover:!bg-red-50 hover:!text-red-700" title="Delete">
                                            <x-ui.icon name="trash" class="size-4" /><span class="sr-only">Delete {{ $page->title }}</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
