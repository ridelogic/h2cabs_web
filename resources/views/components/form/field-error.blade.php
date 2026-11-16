@props(['name', 'id' => null])

@error($name)
    <p id="{{ $id ?? 'field-'.$name }}-error" class="form-error"><x-ui.icon name="alert" class="mt-0.5 size-4" />{{ $message }}</p>
@enderror
