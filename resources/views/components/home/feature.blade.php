@props(['icon', 'title'])

<div {{ $attributes->merge(['class' => 'flex gap-4']) }}>
    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-green-100 text-brand-green-700">
        <x-ui.icon :name="$icon" />
    </span>
    <div>
        <h3 class="text-lg font-bold text-brand-blue-950">{{ $title }}</h3>
        <p class="mt-1 text-muted">{{ $slot }}</p>
    </div>
</div>
