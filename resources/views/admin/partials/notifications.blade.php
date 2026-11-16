{{-- Body of the top-bar notifications menu (fetched by resources/js/admin.js). --}}
@if ($notifications->isEmpty())
    <div class="flex flex-col items-center px-6 py-8 text-center">
        <span class="admin-tile size-12 bg-admin-accent text-brand-blue-600"><x-ui.icon name="bell" class="size-6" /></span>
        <p class="mt-3 text-sm font-semibold text-admin-navy">You're all caught up</p>
        <p class="mt-0.5 text-xs text-slate-500">New booking requests will appear here.</p>
    </div>
@else
    <ul class="max-h-80 divide-y divide-admin-line overflow-y-auto">
        @foreach ($notifications as $notice)
            <li>
                <a href="{{ route('admin.bookings.show', $notice) }}" class="flex gap-3 px-4 py-3 transition hover:bg-admin-accent/60">
                    <span class="admin-tile size-9 bg-violet-50 text-violet-600"><x-ui.icon :name="$notice->booking_type->icon()" class="size-4" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-admin-navy">{{ $notice->customer_name }} <span class="font-mono text-xs font-medium text-slate-500">{{ $notice->reference }}</span></span>
                        <span class="block truncate text-xs text-slate-500">{{ $notice->booking_type->label() }} · {{ $notice->pickup }}</span>
                        <span class="block text-[0.6875rem] text-slate-400">{{ $notice->created_at?->diffForHumans() }}</span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
