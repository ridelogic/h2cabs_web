{{--
    Shown after a valid booking form. The request is stored (status "new")
    but NOT confirmed, and the WhatsApp message is NOT sent until the
    customer presses Send in WhatsApp. Wording must stay accurate to that.
--}}
<x-layouts.app :seo="$seo">
    <section class="section bg-surface" aria-labelledby="page-title">
        <div class="container-site max-w-3xl">
            <div class="card overflow-hidden">
                <div class="bg-gradient-to-br from-brand-blue-800 to-brand-green-700 px-6 py-8 text-white sm:px-10">
                    <span class="flex size-12 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/30"><x-ui.icon name="whatsapp" /></span>
                    <h1 id="page-title" class="heading-display mt-4 text-3xl sm:text-4xl">
                        @if ($whatsappUrl) One last step: send your request on WhatsApp @else We have received your ride request @endif
                    </h1>
                    <p class="mt-4 inline-flex flex-wrap items-baseline gap-x-2 rounded-xl bg-white/15 px-4 py-2 ring-1 ring-white/25">
                        <span class="text-sm text-white/80">Booking reference</span>
                        <strong class="font-mono text-xl tracking-wide" data-booking-reference>{{ $reference }}</strong>
                    </p>
                    <p class="mt-3 text-white/85">
                        @if ($whatsappUrl)
                            Your request is saved, but the WhatsApp message has <strong>not been sent yet</strong>. Open WhatsApp, check the message and press <strong>Send</strong>.
                        @else
                            Your request is saved with the reference above. Please quote it when you contact us.
                        @endif
                        This is a request, not a confirmed booking — H2 CABS will confirm availability and the fare with you.
                    </p>
                </div>

                <div class="p-6 sm:p-10">
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-whatsapp w-full py-4 text-lg" data-whatsapp-link data-whatsapp-beacon="{{ route('booking.whatsapp-opened') }}" data-csrf="{{ csrf_token() }}">
                            <x-ui.icon name="whatsapp" class="size-6" /> Open WhatsApp to send
                        </a>
                        <p class="mt-3 text-center text-sm text-muted">Opens WhatsApp with your message filled in. Nothing is sent until you press Send in WhatsApp.</p>
                    @else
                        <div class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-900 ring-1 ring-amber-200" role="note">
                            <p class="font-semibold">WhatsApp booking is not available at the moment.</p>
                            <p class="mt-1">Your request has been saved. You can also copy the summary below and send it to us using the details on our <a href="{{ route('contact') }}" class="font-semibold underline">contact page</a>.</p>
                            @if (app()->isLocal())
                                <p class="mt-2 font-mono text-xs">Developer note: set H2CABS_WHATSAPP in .env to enable the WhatsApp link.</p>
                            @endif
                        </div>
                    @endif

                    <h2 class="mt-10 text-lg font-bold text-brand-blue-950">Your trip details</h2>
                    <dl class="mt-4 divide-y divide-line rounded-2xl border border-line">
                        @foreach ($rows as $row)
                            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4">
                                <dt class="text-sm font-semibold text-muted">{{ $row['label'] }}</dt>
                                <dd class="text-ink break-words whitespace-pre-line">{{ $row['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($fareNotes)
                        <div class="mt-4 rounded-2xl bg-brand-blue-50 p-4 text-sm text-ink" role="note">
                            <p class="font-semibold text-brand-blue-950">About the fare</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($fareNotes as $note)<li>{{ $note }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <details class="mt-6 rounded-2xl bg-surface p-5">
                        <summary class="cursor-pointer font-semibold text-brand-blue-900">Show the message text</summary>
                        <pre class="mt-4 font-sans text-sm break-words whitespace-pre-wrap text-ink" data-message-text>{{ $message }}</pre>
                        <button type="button" class="btn btn-outline mt-4" data-copy-message hidden>
                            <x-ui.icon name="copy" class="size-4" /> <span data-copy-label>Copy message</span>
                        </button>
                    </details>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('booking.create', ['edit' => 1]) }}" class="btn btn-outline">
                            <x-ui.icon name="edit" class="size-4" /> Edit details
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline">Back to home</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
