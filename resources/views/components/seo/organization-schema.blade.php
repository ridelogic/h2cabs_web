{{--
    schema.org structured data. Only fields backed by configuration are
    output; phone/address/hours appear once they are set in .env.
--}}
@php
    $contact = config('site.contact');

    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'TaxiService',
        'name' => config('site.brand.name'),
        'alternateName' => config('site.brand.full_name'),
        'url' => route('home'),
        'logo' => asset(config('site.brand.logo')),
        'image' => asset(config('site.seo.default_image')),
        'description' => config('site.seo.default_description'),
        'areaServed' => [
            '@type' => 'City',
            'name' => config('site.region.city'),
        ],
        'provider' => [
            '@type' => 'Organization',
            'name' => config('site.brand.full_name'),
        ],
        'telephone' => $contact['phone'],
        'email' => $contact['email'],
        'sameAs' => array_values(array_filter(config('site.social'))) ?: null,
    ]);
@endphp

<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
