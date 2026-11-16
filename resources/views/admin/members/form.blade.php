<x-layouts.admin :title="$member->exists ? 'Edit member' : 'Add member'">
    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" enctype="multipart/form-data" class="card space-y-6 p-6 sm:p-8"
            action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}">
            @csrf
            @if ($member->exists) @method('PUT') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.field name="name" label="Full name" :value="$member->name" required maxlength="120" class="sm:col-span-2" />
                <x-admin.field name="role" label="Role / designation" :value="$member->role" maxlength="80" help="e.g. President, Treasurer, Executive Member" />
                <div>
                    <label for="admin-member_group" class="form-label">Group</label>
                    <select id="admin-member_group" name="member_group" class="form-control">
                        @foreach (\App\Models\Member::GROUPS as $value => $label)
                            <option value="{{ $value }}" @selected(old('member_group', $member->member_group) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-admin.field name="phone" label="Public contact number" :value="$member->phone" type="tel" maxlength="20" help="Optional. Only add if the member agrees to publish it." />
                <x-admin.field name="member_since" label="Member since (year)" :value="$member->member_since" type="number" min="1950" :max="now()->year" />
                <div class="sm:col-span-2">
                    <label for="admin-bio" class="form-label">Short description</label>
                    <textarea id="admin-bio" name="bio" rows="3" maxlength="500" class="form-control">{{ old('bio', $member->bio) }}</textarea>
                    <x-form.field-error name="bio" id="admin-bio" />
                </div>
                <x-admin.field name="sort_order" label="Display order" :value="$member->sort_order" type="number" min="0" help="Lower numbers appear first. Leave blank to keep the current position." />
            </div>

            <fieldset class="space-y-4 border-t border-line pt-6">
                <legend class="sr-only">Visibility</legend>
                <x-admin.switch name="is_active" label="Active member" :checked="$member->is_active" help="Inactive members are never shown publicly." />
                <x-admin.switch name="is_published" label="Show on public website" :checked="$member->is_published" help="Hide a member without deleting their record." />
            </fieldset>

            <fieldset class="border-t border-line pt-6">
                <legend class="form-label">Photo</legend>
                @if ($member->photoUrl())
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ $member->photoUrl() }}" alt="Current photo of {{ $member->name }}" class="size-20 rounded-full object-cover">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_photo" value="1" class="size-4 accent-red-600"> Remove photo</label>
                    </div>
                @endif
                <input type="file" id="admin-photo" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="admin-photo-help" class="block w-full text-sm file:mr-4 file:rounded-full file:border-0 file:bg-brand-blue-50 file:px-4 file:py-2 file:font-semibold file:text-brand-blue-800">
                <p id="admin-photo-help" class="form-help">JPG, PNG or WebP, up to 2 MB, at least 120×120 px. Use only photos the member has approved.</p>
                <x-form.field-error name="photo" id="admin-photo" />
            </fieldset>

            <div class="flex flex-wrap gap-3 border-t border-line pt-6">
                <button type="submit" class="btn btn-primary">{{ $member->exists ? 'Save changes' : 'Add member' }}</button>
                <a href="{{ route('admin.members.index') }}" class="btn btn-outline">Back to members</a>
            </div>
        </form>

        <aside class="space-y-6">
            <div>
                <h2 class="mb-3 text-sm font-bold tracking-wide text-muted uppercase">Public card preview</h2>
                @if ($member->exists)
                    <x-about.member-card :member="$member" />
                    <p class="mt-3 text-sm">
                        @if ($member->isPublic())
                            <x-admin.badge tone="green">Visible on About page</x-admin.badge>
                        @else
                            <x-admin.badge tone="amber">Not visible publicly</x-admin.badge>
                        @endif
                    </p>
                @else
                    <p class="text-sm text-muted">Save the member to see the preview.</p>
                @endif
            </div>

            @if ($member->exists)
                <form method="POST" action="{{ route('admin.members.destroy', $member) }}" class="card border-red-200 p-5"
                    data-confirm="Delete {{ $member->name }}? This removes them from the dashboard and website." onsubmit="return confirm(this.dataset.confirm)">
                    @csrf @method('DELETE')
                    <h2 class="font-bold text-red-800">Delete member</h2>
                    <p class="mt-1 text-sm text-muted">To keep the record, hide the member instead.</p>
                    <button type="submit" class="btn mt-4 border border-red-300 text-red-700 hover:bg-red-50">Delete</button>
                </form>
            @endif
        </aside>
    </div>
</x-layouts.admin>
