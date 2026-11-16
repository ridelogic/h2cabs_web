<?php

namespace Tests\Feature;

use App\Models\FareRule;
use App\Models\Member;
use Database\Seeders\ReferenceMembersSeeder;
use Database\Seeders\VehicleFareSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['home', 'H2 CABS — Taxi Service in Pondicherry', 'Taxi Rentals'],
            'about' => ['about', 'About the Helping Hand Drivers Association | H2 CABS', 'The Helping Hand Drivers Association'],
            'services' => ['services', 'Taxi Services in Pondicherry | H2 CABS', 'Taxi services in and around Pondicherry'],
            'destinations' => ['destinations', 'Places to Visit in Pondicherry | H2 CABS', 'Places to visit in and around Pondicherry'],
            'booking' => ['booking.create', 'Book a Taxi in Pondicherry | H2 CABS', 'Book your H2 CABS taxi'],
            'contact' => ['contact', 'Contact Us | H2 CABS', 'Get in touch with H2 CABS'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_returns_ok_with_its_title_and_heading(string $route, string $title, string $heading): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString('<title>'.e($title).'</title>', $html);
        $this->assertSame(1, substr_count($html, '<h1'), "{$route} should have exactly one h1.");
        $this->assertMatchesRegularExpression('#<h1[^>]*>\s*'.preg_quote(e($heading), '#').'#', $html);
    }

    #[DataProvider('pages')]
    public function test_page_has_complete_seo_metadata(string $route): void
    {
        $html = $this->get(route($route))->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.route($route).'">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.route($route).'">', $html);
        $this->assertSame(1, substr_count($html, 'name="description"'));
        $this->assertSame(1, substr_count($html, 'property="og:title"'));
        $this->assertStringContainsString('<main id="main"', $html);
    }

    public function test_every_page_has_a_unique_title_and_description(): void
    {
        $titles = [];
        $descriptions = [];

        foreach (self::pages() as [$route]) {
            $html = $this->get(route($route))->getContent();
            preg_match('#<title>(.*?)</title>#', $html, $title);
            preg_match('#<meta name="description" content="(.*?)">#', $html, $description);
            $titles[] = $title[1];
            $descriptions[] = $description[1];
        }

        $this->assertSame($titles, array_unique($titles));
        $this->assertSame($descriptions, array_unique($descriptions));
    }

    #[DataProvider('pages')]
    public function test_navigation_links_to_every_page_and_marks_the_current_one(string $route): void
    {
        $html = $this->get(route($route))->getContent();

        foreach (config('site.navigation') as $item) {
            $this->assertStringContainsString('href="'.route($item['route']).'"', $html, "Missing nav link to {$item['route']}.");
        }

        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route($route), '#').'"\s+aria-current="page"#', $html);
    }

    public function test_navigation_items_are_valid_named_routes(): void
    {
        foreach (config('site.navigation') as $item) {
            $this->assertTrue(app('router')->has($item['route']), "Route {$item['route']} does not exist.");
        }

        $this->assertSame(
            ['Home', 'About Us', 'Our Services', 'Destinations', 'Booking', 'Contact Us'],
            array_column(config('site.navigation'), 'label'),
        );
    }

    public function test_inner_pages_output_breadcrumb_structured_data(): void
    {
        $html = $this->get(route('services'))->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $types = array_map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR)['@type'], $matches[1]);

        $this->assertContains('BreadcrumbList', $types);
        $this->assertContains('TaxiService', $types);
    }

    public function test_header_call_and_whatsapp_buttons_only_appear_when_configured(): void
    {
        config(['site.contact.phone' => null, 'site.contact.whatsapp' => null]);

        $this->get(route('about'))
            ->assertDontSee('href="tel:', false)
            ->assertDontSee('wa.me', false);

        config(['site.contact.phone' => '0413 222 2222', 'site.contact.whatsapp' => '98765 43210']);

        $this->get(route('about'))
            ->assertSee('href="tel:04132222222"', false)
            ->assertSee('https://wa.me/919876543210', false);
    }

    public function test_invalid_whatsapp_number_produces_no_link(): void
    {
        config(['site.contact.whatsapp' => '12345']);

        $this->get(route('home'))->assertDontSee('wa.me', false);
    }

    public function test_services_page_presents_the_four_booking_types_with_book_now_links(): void
    {
        $response = $this->get(route('services'));

        $response->assertSeeInOrder(['Four booking types', 'Local', 'Rental', 'Outstation One Way', 'Outstation Round Trip']);
        $response->assertSee(['id="outstation"', 'How outstation booking works', 'Fares and what affects them'], false);
        $response->assertSee(['Sightseeing', 'Airport &amp; Railway Transfers', 'Puducherry → Chennai'], false);
        // Page content uses the booking-type names. ("Local Taxi" is a header link label.)
        $main = str($response->getContent())->between('<main', '</main>')->toString();
        $this->assertStringNotContainsString('One-Way Trips', $main);
        $this->assertStringNotContainsString('Local Taxi', $main);

        foreach (['local', 'rental', 'outstation_one_way', 'outstation_round_trip'] as $type) {
            $response->assertSee(route('booking.create', ['type' => $type]), false);
        }
    }

    public function test_services_page_lists_active_vehicles_and_publishes_only_approved_fares(): void
    {
        $this->seed(VehicleFareSeeder::class);

        // Active vehicles are listed; proposed (unapproved) fares are not
        // published, and the vehicles cannot be chosen for booking yet.
        $this->get(route('services'))
            ->assertOk()
            ->assertSee('id="vehicles"', false)
            ->assertSee(['Sedan', 'SUV', 'Fare available on request'])
            ->assertDontSee(route('booking.create', ['vehicle' => 'suv']), false)
            ->assertDontSee('₹13/km')
            ->assertDontSee('Fare confirmed on booking')
            ->assertDontSee('Proposed');

        FareRule::query()->update(['is_approved' => true]);

        $this->get(route('services'))
            ->assertSee(['Sedan', 'SUV', 'Up to 4 passengers', '₹13/km', '₹14/km'])
            ->assertSee(route('booking.create', ['vehicle' => 'suv']), false)
            ->assertDontSee('₹16/km'); // Premium is inactive
    }

    public function test_services_page_hides_vehicle_section_without_vehicles(): void
    {
        $this->get(route('services'))->assertOk()->assertDontSee('id="vehicles"', false)->assertDontSee('₹');
    }

    public function test_destinations_page_lists_places_with_images_and_booking_links(): void
    {
        $response = $this->get(route('destinations'));

        $response->assertSee(['Promenade &amp; Rock Beach', 'French Quarter (White Town)', 'Auroville', 'Paradise Beach', 'Chunnambar Boat House', 'Sri Aurobindo Ashram'], false);
        $response->assertSee('images/photos/chunnambar-800.webp', false);
        $response->assertSee(route('booking.create', ['destination' => 'paradise-beach']), false);
    }

    public function test_about_page_shows_placeholder_when_no_members_are_published(): void
    {
        Member::factory()->unpublished()->create(['name' => 'Hidden Member']);

        $this->get(route('about'))
            ->assertSee('Member profiles coming soon')
            ->assertDontSee('Hidden Member');
    }

    public function test_about_page_lists_published_members_in_order(): void
    {
        Member::factory()->create(['name' => 'Second Person', 'role' => 'Driver', 'sort_order' => 2]);
        Member::factory()->create(['name' => 'First Person', 'role' => 'Treasurer', 'sort_order' => 1]);

        $this->get(route('about'))
            ->assertDontSee('Member profiles coming soon')
            ->assertSeeInOrder(['Treasurer', 'First Person', 'Driver', 'Second Person']);
    }

    public function test_about_page_shows_only_active_and_visible_reference_members(): void
    {
        $this->seed(ReferenceMembersSeeder::class);
        Member::where('name', 'T. Suresh')->update(['is_active' => false]);
        Member::where('name', 'P. Senthil')->update(['is_published' => false]);

        $this->get(route('about'))
            ->assertSee(config('site.association.name'))
            ->assertSeeInOrder(['Office bearers', 'President', 'G. Prabaharan', 'Vice-President', 'H. Ashraff Ali', 'Executive members', 'P. Kumar'])
            ->assertSee('S. Arun Kumar')
            ->assertDontSee('T. Suresh')
            ->assertDontSee('P. Senthil');
    }

    public function test_photo_credits_page_attributes_every_photo(): void
    {
        $response = $this->get(route('photo-credits'))->assertOk();

        foreach (config('photos') as $key => $photo) {
            $response->assertSee($photo['author']);
            $response->assertSee($photo['license']);
            $this->assertFileExists(public_path("images/photos/{$key}-{$photo['widths'][0]}.webp"));
        }
    }
}
