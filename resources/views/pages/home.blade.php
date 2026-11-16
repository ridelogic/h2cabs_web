<x-layouts.app :seo="$seo">
    @include('pages.home.hero')
    @include('pages.home.booking')
    @include('pages.home.vehicles')
    @include('pages.home.services')
    @include('pages.home.outstation')
    @include('pages.home.destinations')
    <x-app-promo />
    @include('pages.home.association')
</x-layouts.app>
