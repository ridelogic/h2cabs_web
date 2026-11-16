<x-layouts.admin title="SEO">
    <p class="max-w-3xl text-muted">
        Edit the title and description search engines show for each public page. Pages you have not customised use the
        built-in defaults. Search engines index the site only when <code>APP_ENV=production</code>.
    </p>

    <div class="card relative mt-6 overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-sm">
            <caption class="sr-only">Public pages</caption>
            <thead class="border-b border-line bg-surface text-xs tracking-wide text-muted uppercase">
                <tr>
                    <th scope="col" class="px-4 py-3">Page</th>
                    <th scope="col" class="px-4 py-3">Title and description</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($pages as $page)
                    <tr>
                        <th scope="row" class="px-4 py-3 align-top">
                            <span class="block font-semibold text-brand-blue-950">{{ $page['label'] }}</span>
                            <a href="{{ $page['url'] }}" target="_blank" rel="noopener" class="text-xs break-all text-muted hover:underline">{{ $page['url'] }}</a>
                        </th>
                        <td class="px-4 py-3 align-top">
                            <span class="block font-medium text-brand-blue-800">{{ $page['seo']->title }}</span>
                            <span class="mt-0.5 block text-muted">{{ \Illuminate\Support\Str::limit($page['seo']->description, 120) }}</span>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-wrap gap-1">
                                @if ($page['customised'])<x-admin.badge tone="blue">Customised</x-admin.badge>@else<x-admin.badge>Default</x-admin.badge>@endif
                                @unless ($page['seo']->indexable || ! config('site.seo.indexable'))<x-admin.badge tone="amber">Hidden from search</x-admin.badge>@endunless
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right align-top">
                            <a href="{{ route('admin.seo.edit', $page['route']) }}" class="font-semibold text-brand-blue-700 hover:underline">Edit<span class="sr-only"> SEO for {{ $page['label'] }}</span></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
