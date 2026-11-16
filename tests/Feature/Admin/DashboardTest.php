<?php

namespace Tests\Feature\Admin;

use App\Models\BookingRequest;
use App\Models\FareRule;
use App\Models\Member;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $attributes = []): BookingRequest
    {
        $booking = new BookingRequest;
        $booking->forceFill([
            'reference' => BookingRequest::newReference(),
            'booking_type' => 'outstation_one_way',
            'status' => BookingRequest::STATUS_NEW,
            'pickup' => 'White Town, Puducherry',
            'dropoff' => 'Chennai Central',
            'travel_date' => '2026-10-20',
            'pickup_time' => '09:30',
            'passengers' => 2,
            'customer_name' => 'Priya Raman',
            'customer_phone' => '9876512345',
            'fare_status' => 'requires_confirmation',
            'whatsapp_status' => BookingRequest::WHATSAPP_LINK_SHOWN,
            ...$attributes,
        ])->save();

        return $booking;
    }

    public function test_dashboard_shows_the_welcome_header_with_real_date_and_actions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 09:00', 'Asia/Kolkata'));

        $this->actingAs($this->admin(['name' => 'Ravi Kumar']))->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Good morning, <span class="text-admin-green">Ravi!</span>', false)
            ->assertSee('Welcome to H2 CABS Admin Portal')
            ->assertSee('Tuesday, 13 October 2026')
            ->assertSee('images/photos/promenade-hero-1280.webp', false)
            ->assertSee('href="'.route('admin.vehicles.create').'"', false)
            ->assertSee(['Add Vehicle', 'View Bookings']);

        $this->assertFileExists(public_path('images/photos/promenade-hero-1280.webp'));
        Carbon::setTestNow();
    }

    public function test_kpis_use_real_counts(): void
    {
        $bookable = Vehicle::factory()->create(['is_active' => true]);
        FareRule::factory()->for($bookable)->create(['booking_type' => 'local', 'is_approved' => true]);
        $proposed = Vehicle::factory()->create(['is_active' => true]);
        FareRule::factory()->for($proposed)->create(['booking_type' => 'local', 'is_approved' => false]);
        Vehicle::factory()->create(['is_active' => false]);
        $this->booking();
        $this->booking(['status' => BookingRequest::STATUS_CONFIRMED]);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSeeInOrder(['Active Vehicles', '2', '1 bookable online'])
            ->assertSeeInOrder(['Active Fares', '2', '1 still proposed'])
            ->assertSeeInOrder(['Booking Requests', '2', '1 new to review']);
    }

    public function test_recent_bookings_show_real_requests_or_an_empty_state(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee('No booking requests yet')
            ->assertSee('View all bookings')
            ->assertDontSee('Priya Raman');

        $booking = $this->booking(['customer_name' => 'Priya <b>Raman</b>']);

        $this->get(route('admin.dashboard'))
            ->assertDontSee('No booking requests yet')
            ->assertSee([$booking->reference, 'Outstation One Way', 'White Town, Puducherry → Chennai Central', 'Tue, 20 Oct 2026, 9:30 AM', 'New request'])
            ->assertSee('Priya &lt;b&gt;Raman&lt;/b&gt;', false)
            ->assertSee('href="'.route('admin.bookings.show', $booking).'"', false);
    }

    public function test_new_request_count_appears_in_the_sidebar_and_bell(): void
    {
        $this->actingAs($this->admin())->get(route('admin.vehicles.index'))
            ->assertSee('No new booking requests')
            ->assertDontSee('aria-label="1 new"', false);

        $this->booking();

        $this->get(route('admin.vehicles.index'))
            ->assertSee('aria-label="1 new"', false)
            ->assertSee('New booking requests: 1')
            ->assertSee('href="'.route('admin.bookings.index', ['status' => 'new']).'"', false);
    }

    public function test_notifications_menu_lists_the_latest_new_requests(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee('Notifications')
            ->assertSee('data-src="'.route('admin.notifications').'"', false);

        $this->get(route('admin.notifications'))->assertOk()->assertSee("You're all caught up", false);

        $new = $this->booking(['customer_name' => 'Waiting Customer']);
        $this->booking(['customer_name' => 'Handled Customer', 'status' => BookingRequest::STATUS_CONFIRMED]);

        $this->get(route('admin.notifications'))
            ->assertDontSee("You're all caught up", false)
            ->assertSee('Waiting Customer')
            ->assertDontSee('Handled Customer')
            ->assertSee('href="'.route('admin.bookings.show', $new).'"', false);
    }

    public function test_top_bar_search_covers_bookings_members_vehicles_and_fares(): void
    {
        $match = $this->booking(['customer_name' => 'Findable Person']);
        $this->booking(['customer_name' => 'Someone Else']);
        $member = Member::factory()->create(['name' => 'Findable Member']);
        $vehicle = Vehicle::factory()->create(['name' => 'Findable Sedan']);
        $fare = FareRule::factory()->for($vehicle)->create(['booking_type' => 'local']);
        Vehicle::factory()->create(['name' => 'Hidden Van']);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee('action="'.route('admin.search').'"', false)
            ->assertSee('name="q"', false);

        $this->get(route('admin.search', ['q' => 'Findable']))
            ->assertOk()
            ->assertSee([$match->reference, 'Findable Member', 'Findable Sedan'])
            ->assertSee('href="'.route('admin.members.edit', $member).'"', false)
            ->assertSee('href="'.route('admin.vehicles.show', $vehicle).'"', false)
            ->assertSee('href="'.route('admin.fares.edit', $fare).'"', false)
            ->assertDontSee(['Someone Else', 'Hidden Van']);

        $this->get(route('admin.search', ['q' => 'zzz-no-match']))->assertOk()->assertSee('No matches found');
        $this->get(route('admin.search'))->assertOk()->assertSee('What are you looking for?');
        $this->get(route('admin.search', ['q' => '100%_']))->assertOk();
    }

    public function test_search_and_notifications_are_admin_only(): void
    {
        $this->get(route('admin.search', ['q' => 'x']))->assertRedirect(route('admin.login'));
        $this->get(route('admin.notifications'))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.search', ['q' => 'x']))->assertForbidden();
        $this->get(route('admin.notifications'))->assertForbidden();
    }

    public function test_setup_checklist_never_reveals_secrets(): void
    {
        config([
            'services.google_maps.browser_key' => 'BROWSER-SECRET-123',
            'services.google_places.key' => 'PLACES-SECRET-456',
            'services.google_routes.key' => null,
        ]);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee(['GOOGLE_MAPS_BROWSER_KEY', 'GOOGLE_ROUTES_API_KEY', 'Configured', 'Not configured'])
            ->assertDontSee('BROWSER-SECRET-123')
            ->assertDontSee('PLACES-SECRET-456');
    }

    public function test_navigation_and_quick_actions_link_to_existing_routes(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->getContent();

        foreach (['admin.dashboard', 'admin.bookings.index', 'admin.members.index', 'admin.vehicles.index', 'admin.vehicles.create', 'admin.fares.index', 'admin.settings.edit', 'admin.seo.index', 'admin.logout'] as $route) {
            $this->assertStringContainsString('"'.route($route).'"', $html, $route);
        }
        $this->assertStringContainsString('aria-current="page"', $html);
        foreach (['Fares &amp; Pricing', 'SEO Management', 'View Website', 'Log out', 'Quick Actions', 'Setup Checklist', 'Recent Booking Requests'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_every_admin_page_uses_the_new_shell(): void
    {
        $this->actingAs($this->admin());
        $vehicle = Vehicle::factory()->create();
        $fare = FareRule::factory()->for($vehicle)->create();
        $booking = $this->booking();

        foreach ([
            route('admin.dashboard'), route('admin.bookings.index'), route('admin.bookings.show', $booking),
            route('admin.members.index'), route('admin.members.create'), route('admin.vehicles.index'),
            route('admin.vehicles.create'), route('admin.vehicles.show', $vehicle), route('admin.vehicles.edit', $vehicle),
            route('admin.fares.index'), route('admin.fares.edit', $fare), route('admin.settings.edit'), route('admin.seo.index'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('data-admin-shell', false)->assertSee('admin-sidebar', false);
        }
    }

    public function test_guests_and_non_admins_cannot_see_the_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.login'))->assertOk()->assertSee('Admin login');
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }
}
