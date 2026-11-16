<x-layouts.app :seo="$seo">
    <section class="section bg-surface" aria-labelledby="page-title">
        <div class="container-site max-w-4xl">
            <nav aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm text-muted">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-blue-700">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-ink">Photo credits</li>
                </ol>
            </nav>
            <h1 id="page-title" class="heading-display mt-6 text-4xl text-brand-blue-950">Photo credits</h1>
            <p class="mt-4 text-lg text-muted">
                The photographs on this website come from Wikimedia Commons and are used under Creative Commons licences.
                All images have been cropped and resized for the web. Images under a ShareAlike (BY-SA) licence remain
                available under the same licence.
            </p>

            <ul class="mt-10 grid gap-4 sm:grid-cols-2">
                @foreach ($photos as $key => $photo)
                    <li class="card flex gap-4 overflow-hidden p-3">
                        <div class="aspect-[3/2] w-28 shrink-0 overflow-hidden rounded-xl">
                            <x-ui.photo :key="$key" sizes="112px" alt="" />
                        </div>
                        <div class="min-w-0 py-1 text-sm">
                            <p class="font-semibold break-words text-brand-blue-950">
                                <a href="{{ $photo['source'] }}" rel="noopener" target="_blank" class="hover:underline">{{ $photo['title'] }}</a>
                            </p>
                            <p class="mt-1 text-muted">By {{ $photo['author'] }}</p>
                            <p class="mt-1">
                                <a href="{{ $photo['license_url'] }}" rel="noopener license" target="_blank" class="font-medium text-brand-green-700 hover:underline">{{ $photo['license'] }}</a>
                                <span class="text-muted">· cropped and resized</span>
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts.app>
