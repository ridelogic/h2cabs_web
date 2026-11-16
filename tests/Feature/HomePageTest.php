<?php

namespace Tests\Feature;

use App\Models\FareRule;
use App\Models\Vehicle;
use Database\Seeders\VehicleFareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_successfully(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('H2 CABS')
            ->assertSee('Helping Hand Drivers Association Cabs');
    }

    public function test_homepage_has_exactly_one_h1_with_the_hero_headline(): void
    {
        $html = $this->get(route('home'))->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertMatchesRegularExpression('#<h1 id="hero-title"[^>]*>Taxi Rentals<br><span class="[^"]*">Made Easy</span></h1>#', $html);
    }

    public function test_hero_matches_the_green_rental_design(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['data-hero-slider', 'H2 CABS', 'RELIABLE · SAFE · ON TIME', 'Taxi Rentals', 'Made Easy','Book a comfortable cab for hourly, half-day or full-day travel.', 'Book Rental', 'Explore Rentals'], false)
            ->assertSee('href="'.route('booking.create', ['type' => 'rental']).'"', false)
            ->assertSee('href="'.route('services').'#types"', false)
            ->assertSee(asset('images/hero/h2cabs-hero.webp'), false)
            ->assertSee(['data-hero-prev', 'data-hero-next'], false);

        $this->assertFileExists(public_path('images/hero/h2cabs-hero.webp'));
    }

    public function test_hero_car_image_can_be_replaced_in_config(): void
    {
        config(['site.home.hero_car' => 'images/hero/custom-car.png']);

        $this->get(route('home'))->assertSee(asset('images/hero/custom-car.png'), false);
    }

    public function test_top_bar_and_header_show_contact_details_and_trip_links(): void
    {
        config([
            'site.contact.phone' => '+91 97903 85802',
            'site.contact.email' => 'info@h2cabservice.com',
            'site.social.facebook' => 'https://facebook.com/h2cabs',
            'site.social.instagram' => null,
        ]);

        $this->get(route('home'))
            ->assertSee('href="tel:+919790385802"', false)
            ->assertSee('href="mailto:info@h2cabservice.com"', false)
            ->assertSee(['+91 97903 85802', 'info@h2cabservice.com', 'Puducherry'])
            ->assertSee('href="https://facebook.com/h2cabs"', false)
            ->assertDontSee('H2 CABS on Instagram')
            ->assertSeeInOrder(['Home', 'About Us', 'Services', 'Local Taxi', 'Rental', 'Outstation', 'Contact', 'Book a Taxi'])
            ->assertSee('data-overlay', false);

        // Inner pages keep the solid header.
        $this->get(route('about'))->assertDontSee('data-overlay', false);
    }

    public function test_trip_type_links_open_the_booking_form_for_that_type(): void
    {
        foreach (['local', 'rental', 'outstation_one_way'] as $type) {
            $this->get(route('home'))->assertSee('href="'.route('booking.create', ['type' => $type]).'"', false);
        }

        $html = $this->get(route('booking.create', ['type' => 'rental']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('booking.create', ['type' => 'rental']), '#').'"\s+aria-current="page"#', $html);
        $this->assertDoesNotMatchRegularExpression('#<a href="'.preg_quote(route('booking.create', ['type' => 'local']), '#').'"\s+aria-current="page"#', $html);
    }

    public function test_sections_follow_the_requested_order_with_the_booking_widget_below_the_hero(): void
    {
        $this->get(route('home'))->assertSeeInOrder([
            'id="hero-title"',
            'id="book"',
            'id="vehicles-title"',
            'id="services-title"',
            'id="outstation-title"',
            'id="destinations-title"',
            'id="app-title"',
            'id="association-title"',
            '<footer',
        ], false);
    }

    public function test_destinations_and_booking_stay_linked_from_the_footer(): void
    {
        $this->get(route('home'))
            ->assertSee('href="'.route('destinations').'"', false)
            ->assertSee('href="'.route('booking.create').'"', false);
    }

    public function test_homepage_widget_is_the_full_booking_form_posting_to_booking(): void
    {
        $this->seed(VehicleFareSeeder::class);

        $this->get(route('home'))
            ->assertSee('action="'.route('booking.store').'"', false)
            ->assertSee(['value="local"', 'value="rental"', 'value="outstation_one_way"', 'value="outstation_round_trip"'], false)
            ->assertSee(['name="pickup"', 'name="dropoff"', 'name="travel_date"', 'name="pickup_time"', 'name="vehicle_id"', 'name="passengers"', 'name="name"', 'name="phone"', 'name="return_date"', 'name="rental_package_id"'], false);
    }

    public function test_vehicle_carousel_lists_active_vehicles_and_offers_only_approved_fares(): void
    {
        $this->seed(VehicleFareSeeder::class);
        $suv = Vehicle::where('slug', 'suv')->first();

        // Seeded rates are proposals: active vehicles are listed, but no rate
        // is shown and they cannot be selected until a fare is approved.
        $this->get(route('home'))
            ->assertSee('SUV')
            ->assertSee('Fare available on request')
            ->assertSee('Enquire about SUV')
            ->assertDontSee('Select SUV')
            ->assertDontSee('₹13/km')
            ->assertDontSee('Fare confirmed on booking')
            ->assertDontSee('Vehicle options are being set up.');

        FareRule::query()->update(['is_approved' => true]);

        $this->get(route('home'))
            ->assertSee('data-carousel-track', false)
            ->assertSee('data-carousel-prev', false)
            ->assertSee('Select SUV')
            ->assertSee('href="'.route('booking.create', ['vehicle' => 'suv']).'" data-select-vehicle="'.$suv->id.'"', false)
            ->assertSee(['₹13/km', '₹14/km'])
            ->assertDontSee('Fare on request') // A rental without packages is not offered.
            ->assertDontSee('Select Premium'); // Inactive vehicle.
    }

    public function test_vehicle_section_shows_an_empty_state_without_vehicles(): void
    {
        $this->get(route('home'))->assertSee('Vehicle options are being set up.');
    }

    public function test_homepage_shows_the_four_services_with_booking_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertSeeInOrder(['Four ways to travel', 'Local', 'Rental', 'Outstation One Way', 'Outstation Round Trip']);
        foreach (['local', 'rental', 'outstation_one_way', 'outstation_round_trip'] as $type) {
            $response->assertSee(route('booking.create', ['type' => $type]), false);
        }
    }

    public function test_outstation_section_never_describes_unapproved_fares(): void
    {
        $this->seed(VehicleFareSeeder::class);

        $this->get(route('home'))
            ->assertSee('What affects the fare')
            ->assertDontSee('Fare confirmed on booking')
            ->assertDontSee('₹13/km')
            ->assertDontSee('₹14/km')
            ->assertSee('return-journey billing is confirmed when you book.')
            ->assertDontSee('the onward and return distance are both counted.');
    }

    public function test_outstation_section_explains_approved_fares_from_configuration(): void
    {
        $this->seed(VehicleFareSeeder::class);
        FareRule::query()->update(['is_approved' => true]);

        $this->get(route('home'))
            ->assertSee('What affects the fare')
            ->assertSee(['₹13/km', '₹14/km', '13 km'])
            ->assertDontSee('Rates are shown once approved.')
            ->assertSee('the onward and return distance are both counted.')
            ->assertSee('Driver allowance:</strong>', false)
            ->assertSee('confirmed when you book, if applicable.')
            ->assertSee('Examples only');
    }

    public function test_homepage_shows_destinations_with_photos_and_plan_links(): void
    {
        $this->get(route('home'))
            ->assertSee(['Promenade &amp; Rock Beach', 'French Quarter (White Town)', 'Auroville', 'Paradise Beach'], false)
            ->assertSee('images/photos/rock-beach-800.webp', false)
            ->assertSee(route('booking.create', ['destination' => 'auroville']), false);
    }

    public function test_homepage_has_no_fake_reviews(): void
    {
        $this->get(route('home'))
            ->assertDontSee('Sample Passenger')
            ->assertDontSee('testimonial', false);
    }

    public function test_missing_contact_details_show_placeholder_instead_of_fake_numbers(): void
    {
        config(['site.contact.phone' => null, 'site.contact.whatsapp' => null, 'site.contact.email' => null]);

        $this->get(route('home'))
            ->assertSee('Contact details coming soon')
            ->assertDontSee('href="tel:', false)
            ->assertDontSee('https://wa.me/', false);
    }

    public function test_configured_contact_details_are_linked(): void
    {
        config(['site.contact.phone' => '+91 90000 00000', 'site.contact.whatsapp' => '+91 90000 00000']);

        $this->get(route('home'))
            ->assertSee('href="tel:+919000000000"', false)
            ->assertSee('https://wa.me/919000000000', false)
            ->assertDontSee('Contact details coming soon');
    }

    public function test_app_promo_says_coming_soon_without_real_links(): void
    {
        config(['site.apps.passenger' => ['android' => null, 'ios' => null]]);

        $this->get(route('home'))
            ->assertSee('Your ride, just a tap away')
            ->assertSee('App coming soon')
            ->assertDontSee('Get it on')
            ->assertDontSee('QR code to download')
            ->assertSee('Download links and QR codes will appear here when the app is published.');
    }

    public function test_app_promo_shows_store_button_and_qr_only_for_configured_platforms(): void
    {
        config(['site.apps.passenger' => ['android' => 'https://play.google.com/store/apps/details?id=example.h2cabs', 'ios' => null]]);

        $html = $this->get(route('home'))
            ->assertSee('Get it on Google Play')
            ->assertDontSee('Get it on the App Store')
            ->assertSee('QR code to download the H2 CABS app from Google Play')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'QR code to download'));
        $this->assertMatchesRegularExpression('#aria-label="QR code to download[^"]*">\s*<svg#', $html);
    }

    public function test_app_promo_ignores_non_https_links(): void
    {
        config(['site.apps.passenger' => ['android' => 'javascript:alert(1)', 'ios' => 'http://example.com']]);

        $this->get(route('home'))->assertSee('App coming soon')->assertDontSee('javascript:alert', false);
    }

    public function test_hero_photo_is_loaded_eagerly_and_other_photos_lazily(): void
    {
        $html = $this->get(route('home'))->getContent();

        $this->assertMatchesRegularExpression('#images/hero/h2cabs-hero\.webp[^>]*fetchpriority="high"#s', $html);
        $this->assertMatchesRegularExpression('#rock-beach-800\.webp[^>]*loading="lazy"#s', $html);
    }
}
