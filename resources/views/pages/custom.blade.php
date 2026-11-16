{{-- A custom page from Admin → Website Pages. Content is rendered by Page::renderedContent() (raw HTML stripped). --}}
<x-layouts.app :seo="$seo">
    @if ($preview)
        <div role="status" class="sticky top-18 z-40 border-b border-amber-200 bg-amber-50 sm:top-20">
            <div class="container-site flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm text-amber-900">
                <p><strong>Preview</strong> — {{ $page->is_published ? 'this page is published.' : 'this draft is only visible to admins.' }}</p>
                <a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold underline">Back to editing</a>
            </div>
        </div>
    @endif

    <article class="section bg-surface" aria-labelledby="page-title">
        <div class="container-site max-w-4xl">
            <nav aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm text-muted">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-blue-700">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="truncate font-semibold text-ink">{{ $page->title }}</li>
                </ol>
            </nav>

            <h1 id="page-title" class="heading-display mt-6 text-4xl text-brand-blue-950 sm:text-5xl">{{ $page->title }}</h1>

            @if ($page->imageUrl())
                <img src="{{ $page->imageUrl() }}" alt="" class="mt-8 aspect-[2/1] w-full rounded-(--radius-card) object-cover shadow-card" fetchpriority="high">
            @endif

            <div class="page-content mt-8">
                {{ $page->renderedContent() }}
            </div>

            <div class="mt-12 flex flex-wrap gap-3 border-t border-line pt-8">
                <a href="{{ route('booking.create') }}" class="btn btn-primary">Book a Ride <x-ui.icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('contact') }}" class="btn btn-outline">Contact us</a>
            </div>
        </div>
    </article>
</x-layouts.app>
