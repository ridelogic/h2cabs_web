{{--
    Contact methods from config/site.php. Unset details are omitted, and a
    clear placeholder is shown when none are configured yet.
--}}
@props(['tone' => 'dark'])

@php
    $contact = config('site.contact');
    $whatsappUrl = \App\Support\WhatsApp::chatUrl('Hello H2 CABS, I have a question.');

    $channels = array_values(array_filter([
        $contact['phone'] ? ['icon' => 'phone', 'label' => 'Call us', 'value' => $contact['phone'], 'href' => 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone'])] : null,
        $whatsappUrl ? ['icon' => 'whatsapp', 'label' => 'WhatsApp', 'value' => $contact['whatsapp'], 'href' => $whatsappUrl] : null,
        $contact['email'] ? ['icon' => 'mail', 'label' => 'Email', 'value' => $contact['email'], 'href' => 'mailto:'.$contact['email']] : null,
        $contact['address'] ? ['icon' => 'pin', 'label' => 'Office', 'value' => $contact['address'], 'href' => null] : null,
        $contact['hours'] ? ['icon' => 'clock', 'label' => 'Hours', 'value' => $contact['hours'], 'href' => null] : null,
    ]));
@endphp

<div {{ $attributes->merge(['class' => 'grid content-start gap-4 sm:grid-cols-2']) }}>
    @forelse ($channels as $channel)
        <x-home.contact-channel :icon="$channel['icon']" :label="$channel['label']" :value="$channel['value']" :href="$channel['href']" />
    @empty
        <div class="rounded-2xl bg-white/10 p-6 ring-1 ring-white/15 sm:col-span-2">
            <p class="font-bold text-white">Contact details coming soon</p>
            <p class="mt-2 text-sm text-white/75">
                Our booking phone number, WhatsApp and email will be published here shortly. In the meantime you can
                prepare your trip on the <a href="{{ route('booking.create') }}" class="font-semibold text-white underline">booking page</a>.
            </p>
        </div>
    @endforelse
</div>
