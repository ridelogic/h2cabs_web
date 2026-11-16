<x-layouts.admin :title="'Booking '.$booking->reference">
    @php
        $tz = config('site.booking.timezone');
    @endphp

    <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-blue-700 hover:underline">
        <x-ui.icon name="arrow-right" class="size-4 rotate-180" /> All booking requests
    </a>

    <div class="mt-4 grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="card p-6 sm:p-8" aria-labelledby="request-title">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="request-title" class="text-lg font-bold text-brand-blue-950">Request details</h2>
                <x-admin.badge :tone="$booking->statusTone()">{{ $booking->statusLabel() }}</x-admin.badge>
            </div>
            <p class="mt-1 text-sm text-muted">Submitted {{ $booking->created_at->timezone($tz)->format('D, j M Y, g:i A') }} (IST)</p>

            <dl class="mt-5 divide-y divide-line rounded-2xl border border-line">
                @foreach ($summary->rows as $row)
                    <div class="grid gap-1 px-5 py-3 sm:grid-cols-[11rem_1fr] sm:gap-4">
                        <dt class="text-sm font-semibold text-muted">{{ $row['label'] }}</dt>
                        <dd class="break-words whitespace-pre-line text-ink">{{ $row['value'] }}</dd>
                    </div>
                @endforeach
                <div class="grid gap-1 px-5 py-3 sm:grid-cols-[11rem_1fr] sm:gap-4">
                    <dt class="text-sm font-semibold text-muted">Fare status</dt>
                    <dd>{{ $booking->fare_status === \App\Fares\FareEstimate::ESTIMATED ? 'Estimated from approved fare settings (not a confirmed quote)' : 'Requires confirmation by H2 CABS' }}</dd>
                </div>
                <div class="grid gap-1 px-5 py-3 sm:grid-cols-[11rem_1fr] sm:gap-4">
                    <dt class="text-sm font-semibold text-muted">WhatsApp</dt>
                    <dd>
                        {{ $booking->whatsappLabel() }}
                        @if ($booking->whatsapp_opened_at)<span class="text-muted">— {{ $booking->whatsapp_opened_at->timezone($tz)->format('j M, g:i A') }}</span>@endif
                        <span class="mt-0.5 block text-xs text-muted">The website cannot see whether the customer pressed Send.</span>
                    </dd>
                </div>
            </dl>

            @if ($summary->fareNotes)
                <h3 class="mt-6 font-semibold text-brand-blue-950">Fare details shown to the customer</h3>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($summary->fareNotes as $note)<li>{{ $note }}</li>@endforeach
                </ul>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="tel:{{ $booking->customer_phone }}" class="btn btn-outline"><x-ui.icon name="phone" class="size-4" /> Call customer</a>
                @if ($customerWhatsApp = \App\Support\WhatsApp::normalize($booking->customer_phone))
                    <a href="https://wa.me/{{ $customerWhatsApp }}" target="_blank" rel="noopener" class="btn btn-outline"><x-ui.icon name="whatsapp" class="size-4" /> WhatsApp customer</a>
                @endif
            </div>
        </section>

        <section class="card h-fit p-6" aria-labelledby="status-title">
            <h2 id="status-title" class="text-lg font-bold text-brand-blue-950">Update status</h2>
            <p class="mt-1 text-sm text-muted">Mark a request confirmed only after you have agreed the trip and fare with the customer.</p>

            <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="mt-5 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label for="booking-status" class="form-label">Status</label>
                    <select id="booking-status" name="status" class="form-control" @error('status') aria-invalid="true" @enderror>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-form.field-error name="status" id="booking-status" />
                </div>
                <div>
                    <label for="booking-admin_notes" class="form-label">Internal notes</label>
                    <textarea id="booking-admin_notes" name="admin_notes" rows="4" maxlength="2000" class="form-control" aria-describedby="booking-admin_notes-help">{{ old('admin_notes', $booking->admin_notes) }}</textarea>
                    <p id="booking-admin_notes-help" class="form-help">Visible to admins only.</p>
                    <x-form.field-error name="admin_notes" id="booking-admin_notes" />
                </div>
                <button type="submit" class="btn btn-primary w-full">Save</button>
            </form>
            @if ($booking->status_changed_at)
                <p class="mt-3 text-xs text-muted">Status last changed {{ $booking->status_changed_at->timezone($tz)->format('j M Y, g:i A') }}.</p>
            @endif
        </section>
    </div>
</x-layouts.admin>
