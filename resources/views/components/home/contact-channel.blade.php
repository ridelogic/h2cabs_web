@props(['icon', 'label', 'value', 'href' => null])

<div class="flex items-start gap-4 rounded-2xl bg-white/10 p-5 ring-1 ring-white/15">
    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-blue-800">
        <x-ui.icon :name="$icon" />
    </span>
    <div class="min-w-0">
        <p class="text-sm font-medium text-white/70">{{ $label }}</p>
        @if ($href)
            <a href="{{ $href }}" class="mt-0.5 block text-lg font-bold break-words text-white hover:underline" @if (str_starts_with($href, 'http')) rel="noopener" target="_blank" @endif>{{ $value }}</a>
        @else
            <p class="mt-0.5 text-lg font-bold break-words text-white">{{ $value }}</p>
        @endif
    </div>
</div>
