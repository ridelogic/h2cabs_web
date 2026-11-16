@props(['steps'])

<ol {{ $attributes->merge(['class' => 'grid gap-6 sm:grid-cols-2 lg:grid-cols-4']) }}>
    @foreach ($steps as $step)
        <li class="relative rounded-(--radius-card) border border-line bg-white p-6 shadow-card">
            <span class="flex size-11 items-center justify-center rounded-full bg-brand-blue-700 font-display text-lg font-bold text-white">{{ $loop->iteration }}</span>
            <h3 class="mt-4 text-lg font-bold text-brand-blue-950">{{ $step['title'] }}</h3>
            <p class="mt-1.5 text-muted">{{ $step['text'] }}</p>
        </li>
    @endforeach
</ol>
