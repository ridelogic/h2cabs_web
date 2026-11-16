{{-- Labelled admin input. `prefix`/`suffix` render units such as ₹ or km. --}}
@props(['name', 'label', 'value' => null, 'type' => 'text', 'help' => null, 'prefix' => null, 'suffix' => null, 'required' => false])

@php($id = 'admin-'.$name)

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    <div class="flex items-stretch">
        @if ($prefix)<span class="flex items-center rounded-l-xl border border-r-0 border-line bg-surface px-3 text-muted">{{ $prefix }}</span>@endif
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ old($name, $value) }}"
            @if ($required) required @endif
            @error($name) aria-invalid="true" @enderror
            @if ($help) aria-describedby="{{ $id }}-help" @endif
            {{ $attributes->except('class')->merge(['class' => 'form-control'.($prefix ? ' rounded-l-none' : '').($suffix ? ' rounded-r-none' : '')]) }}>
        @if ($suffix)<span class="flex items-center rounded-r-xl border border-l-0 border-line bg-surface px-3 text-muted">{{ $suffix }}</span>@endif
    </div>
    @if ($help)<p id="{{ $id }}-help" class="form-help">{{ $help }}</p>@endif
    <x-form.field-error :name="$name" :id="$id" />
</div>
