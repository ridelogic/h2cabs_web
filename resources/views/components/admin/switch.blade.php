{{-- Accessible on/off checkbox; posts 1 when checked and 0 otherwise. --}}
@props(['name', 'label', 'checked' => false, 'help' => null])

<div>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="flex cursor-pointer items-start gap-3">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked)) class="mt-1 size-5 accent-brand-green-600">
        <span>
            <span class="block font-semibold text-brand-blue-950">{{ $label }}</span>
            @if ($help)<span class="block text-sm text-muted">{{ $help }}</span>@endif
        </span>
    </label>
</div>
