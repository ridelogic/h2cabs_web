<x-layouts.admin :title="$vehicle->exists ? 'Edit '.$vehicle->name : 'Add vehicle'">
    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" enctype="multipart/form-data" class="card space-y-6 p-6 sm:p-8"
            action="{{ $vehicle->exists ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}">
            @csrf
            @if ($vehicle->exists) @method('PUT') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.field name="name" label="Display name" :value="$vehicle->name" required maxlength="80" help="e.g. Sedan, SUV" />
                <div>
                    <label for="admin-category" class="form-label">Category <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="admin-category" name="category" required class="form-control" @error('category') aria-invalid="true" @enderror>
                        @foreach (\App\Models\Vehicle::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $vehicle->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-form.field-error name="category" id="admin-category" />
                </div>
                <x-admin.field name="slug" label="URL key" :value="$vehicle->slug" maxlength="80" help="Letters, numbers and dashes. Leave blank to generate from the name." />
                <x-admin.field name="sort_order" label="Display order" :value="$vehicle->sort_order" type="number" min="0" />
                <x-admin.field name="passenger_capacity" label="Passenger capacity" :value="$vehicle->passenger_capacity" type="number" min="1" max="60" help="Leave blank if not verified." />
                <x-admin.field name="luggage_capacity" label="Luggage capacity (bags)" :value="$vehicle->luggage_capacity" type="number" min="0" max="60" help="Leave blank if not verified." />
                <div class="sm:col-span-2">
                    <label for="admin-description" class="form-label">Description</label>
                    <textarea id="admin-description" name="description" rows="2" maxlength="300" class="form-control">{{ old('description', $vehicle->description) }}</textarea>
                    <x-form.field-error name="description" id="admin-description" />
                </div>
            </div>

            <div class="border-t border-line pt-6">
                <x-admin.switch name="is_active" label="Active" :checked="$vehicle->is_active" help="Inactive vehicles are hidden from the booking form." />
            </div>

            <fieldset class="border-t border-line pt-6">
                <legend class="form-label">Image (optional)</legend>
                @if ($vehicle->imageUrl())
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ $vehicle->imageUrl() }}" alt="" class="h-16 w-24 rounded-lg object-cover">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1" class="size-4 accent-red-600"> Remove image</label>
                    </div>
                @endif
                <input type="file" name="image" id="admin-image" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-4 file:rounded-full file:border-0 file:bg-brand-blue-50 file:px-4 file:py-2 file:font-semibold file:text-brand-blue-800">
                <p class="form-help">JPG, PNG or WebP up to 2 MB. Use your own or properly licensed photos.</p>
                <x-form.field-error name="image" id="admin-image" />
            </fieldset>

            <div class="flex flex-wrap gap-3 border-t border-line pt-6">
                <button type="submit" class="btn btn-primary">{{ $vehicle->exists ? 'Save changes' : 'Add vehicle' }}</button>
                @if ($vehicle->exists)
                    <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="btn btn-outline">View details</a>
                @endif
                <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline">Back</a>
            </div>
        </form>

        @if ($vehicle->exists)
            <aside>
                <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}" class="card p-5"
                    data-confirm="Delete {{ $vehicle->name }} and all its fares?" onsubmit="return confirm(this.dataset.confirm)">
                    @csrf @method('DELETE')
                    <h2 class="font-bold text-red-800">Delete vehicle</h2>
                    <p class="mt-1 text-sm text-muted">This also deletes its fares. To keep them, mark the vehicle inactive instead.</p>
                    <button type="submit" class="btn mt-4 border border-red-300 text-red-700 hover:bg-red-50">Delete</button>
                </form>
            </aside>
        @endif
    </div>
</x-layouts.admin>
