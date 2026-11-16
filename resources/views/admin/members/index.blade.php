<x-layouts.admin title="Association members">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-muted">Only members that are <strong>active</strong> and <strong>shown on website</strong> appear on the About page, in this order.</p>
        <a href="{{ route('admin.members.create') }}" class="btn btn-primary"><x-ui.icon name="user" class="size-4" /> Add member</a>
    </div>

    <div class="card relative mt-6 overflow-x-auto">
        <table class="w-full min-w-[46rem] text-left text-sm">
            <caption class="sr-only">Association members</caption>
            <thead class="border-b border-line bg-surface text-xs tracking-wide text-muted uppercase">
                <tr>
                    <th scope="col" class="px-4 py-3">Order</th>
                    <th scope="col" class="px-4 py-3">Member</th>
                    <th scope="col" class="px-4 py-3">Group</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Website</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($members as $member)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                @foreach (['up' => 'Move up', 'down' => 'Move down'] as $direction => $label)
                                    <form method="POST" action="{{ route('admin.members.move', $member) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="direction" value="{{ $direction }}">
                                        <button type="submit" class="flex size-8 items-center justify-center rounded-lg border border-line hover:bg-brand-blue-50 disabled:opacity-30"
                                            aria-label="{{ $label }}: {{ $member->name }}" @disabled(($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last))>
                                            <x-ui.icon name="arrow-right" :class="$direction === 'up' ? 'size-4 -rotate-90' : 'size-4 rotate-90'" />
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($member->photoUrl())
                                    <img src="{{ $member->photoUrl() }}" alt="" class="size-10 rounded-full object-cover">
                                @else
                                    <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-full bg-brand-blue-50 text-xs font-bold text-brand-blue-700">{{ $member->initials() }}</span>
                                @endif
                                <span>
                                    <span class="block font-semibold text-brand-blue-950">{{ $member->name }}</span>
                                    <span class="block text-muted">{{ $member->role ?: '—' }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $member->groupLabel() }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.members.toggle', $member) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="is_active">
                                <button type="submit" aria-label="{{ $member->is_active ? 'Deactivate' : 'Activate' }} {{ $member->name }}">
                                    @if ($member->is_active)<x-admin.badge tone="green">Active</x-admin.badge>@else<x-admin.badge>Inactive</x-admin.badge>@endif
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.members.toggle', $member) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="is_published">
                                <button type="submit" aria-label="{{ $member->is_published ? 'Hide' : 'Show' }} {{ $member->name }} on website">
                                    @if ($member->is_published)<x-admin.badge tone="blue">Shown</x-admin.badge>@else<x-admin.badge>Hidden</x-admin.badge>@endif
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.members.edit', $member) }}" class="font-semibold text-brand-blue-700 hover:underline">Edit<span class="sr-only"> {{ $member->name }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-muted">No members yet. Run <code>php artisan db:seed --force</code> to import the reference members, or add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-sm text-muted">Click a status badge to toggle it.</p>
</x-layouts.admin>
