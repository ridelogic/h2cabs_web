{{-- Testimonial card. `sample` marks placeholder content visibly; keep it true until real, consented reviews are added. --}}
@props(['testimonial', 'sample' => true])

<figure class="card flex h-full flex-col p-7">
    <div class="flex items-start justify-between gap-4">
        <x-ui.icon name="quote" class="size-8 text-brand-green-500" />
        @if ($sample)
            <span class="pill bg-amber-100 text-amber-900">Sample content</span>
        @endif
    </div>
    <blockquote class="mt-4 flex-1 text-lg leading-relaxed text-ink">
        <p>{{ $testimonial['quote'] }}</p>
    </blockquote>
    <figcaption class="mt-6 border-t border-line pt-4">
        <span class="block font-bold text-brand-blue-950">{{ $testimonial['name'] }}</span>
        <span class="block text-sm text-muted">{{ $testimonial['context'] }}</span>
    </figcaption>
</figure>
