<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingType;
use App\Fares\FareEstimate;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    private function request(array $attributes = []): BookingRequest
    {
        $booking = new BookingRequest;
        $booking->forceFill(array_merge([
            'reference' => BookingRequest::newReference(),
            'booking_type' => BookingType::OutstationOneWay,
            'status' => BookingRequest::STATUS_NEW,
            'vehicle_name' => 'Sedan',
            'pickup' => 'White Town, Puducherry',
            'pickup_place_id' => 'ChIJmockPickup01',
            'dropoff' => 'Chennai Central',
            'dropoff_place_id' => 'ChIJmockChennai01',
            'travel_date' => '2026-10-20',
            'pickup_time' => '09:30',
            'passengers' => 2,
            'customer_name' => 'Priya Raman',
            'customer_phone' => '9876512345',
            'fare_status' => FareEstimate::REQUIRES_CONFIRMATION,
            'fare_summary' => 'Fare will be confirmed by H2 CABS',
            'whatsapp_status' => BookingRequest::WHATSAPP_LINK_SHOWN,
        ], $attributes))->save();

        return $booking;
    }

    public function test_booking_requests_are_admin_only(): void
    {
        $booking = $this->request();

        foreach ([route('admin.bookings.index'), route('admin.bookings.show', $booking)] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
            $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
            auth()->logout();
        }

        $this->patch(route('admin.bookings.update', $booking), ['status' => 'confirmed'])->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create())->patch(route('admin.bookings.update', $booking), ['status' => 'confirmed'])->assertForbidden();
        $this->assertSame(BookingRequest::STATUS_NEW, $booking->fresh()->status);
    }

    public function test_there_is_no_public_route_to_booking_records(): void
    {
        $booking = $this->request();

        $this->get('/booking/'.$booking->id)->assertNotFound();
        $this->get('/booking/'.$booking->reference)->assertNotFound();
        $this->get(route('booking.confirmation'))->assertRedirect(route('booking.create'));
    }

    public function test_admin_menu_links_to_booking_requests(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee('Booking Requests')
            ->assertSee(route('admin.bookings.index'), false);
    }

    public function test_list_shows_requests_newest_first(): void
    {
        $older = $this->request(['customer_name' => 'Older Customer']);
        $newer = $this->request(['customer_name' => 'Newer Customer', 'fare_amount' => '1300.00', 'fare_status' => FareEstimate::ESTIMATED]);

        $this->actingAs($this->admin())->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSeeInOrder([$newer->reference, $older->reference])
            ->assertSee('₹1,300')
            ->assertSee('To be confirmed')
            ->assertSee('New request')
            ->assertSee('2 new requests.');
    }

    public function test_search_by_reference_name_or_phone(): void
    {
        $priya = $this->request();
        $arun = $this->request(['customer_name' => 'Arun Kumar', 'customer_phone' => '9123456780']);
        $this->actingAs($this->admin());

        $this->get(route('admin.bookings.index', ['q' => $priya->reference]))->assertSee($priya->reference)->assertDontSee($arun->reference);
        $this->get(route('admin.bookings.index', ['q' => 'arun']))->assertSee($arun->reference)->assertDontSee($priya->reference);
        $this->get(route('admin.bookings.index', ['q' => '91234 56780']))->assertSee($arun->reference)->assertDontSee($priya->reference);
        $this->get(route('admin.bookings.index', ['q' => 'nobody']))->assertSee('No booking requests match these filters.');
        // LIKE wildcards are matched literally.
        $this->get(route('admin.bookings.index', ['q' => '%']))->assertSee('No booking requests match these filters.');
    }

    public function test_filter_by_type_status_and_travel_date(): void
    {
        $local = $this->request(['booking_type' => BookingType::Local, 'travel_date' => '2026-10-15']);
        $rental = $this->request(['booking_type' => BookingType::Rental, 'travel_date' => '2026-10-25', 'status' => BookingRequest::STATUS_CONFIRMED]);
        $this->actingAs($this->admin());

        $this->get(route('admin.bookings.index', ['type' => 'local']))->assertSee($local->reference)->assertDontSee($rental->reference);
        $this->get(route('admin.bookings.index', ['status' => 'confirmed']))->assertSee($rental->reference)->assertDontSee($local->reference);
        $this->get(route('admin.bookings.index', ['from' => '2026-10-20']))->assertSee($rental->reference)->assertDontSee($local->reference);
        $this->get(route('admin.bookings.index', ['to' => '2026-10-20']))->assertSee($local->reference)->assertDontSee($rental->reference);

        $this->get(route('admin.bookings.index', ['type' => 'teleport']))->assertSessionHasErrors('type');
        $this->get(route('admin.bookings.index', ['status' => 'paid']))->assertSessionHasErrors('status');
    }

    public function test_detail_view_shows_the_stored_request(): void
    {
        $booking = $this->request(['notes' => '<b>Child seat</b>']);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee(['Outstation One Way', 'Sedan', 'White Town, Puducherry', 'Chennai Central', 'Priya Raman', '9876512345'])
            ->assertSee('Requires confirmation by H2 CABS')
            ->assertSee('WhatsApp link shown')
            ->assertSee('cannot see whether the customer pressed Send')
            ->assertSee('&lt;b&gt;Child seat&lt;/b&gt;', false)
            ->assertSee('href="tel:9876512345"', false)
            ->assertSee('https://wa.me/919876512345', false);
    }

    public function test_admin_can_update_status_and_notes(): void
    {
        $booking = $this->request();

        $this->actingAs($this->admin())
            ->patch(route('admin.bookings.update', $booking), ['status' => 'confirmed', 'admin_notes' => 'Driver Ravi assigned'])
            ->assertRedirect(route('admin.bookings.show', $booking))
            ->assertSessionHas('status');

        $booking->refresh();
        $this->assertSame(BookingRequest::STATUS_CONFIRMED, $booking->status);
        $this->assertSame('Driver Ravi assigned', $booking->admin_notes);
        $this->assertNotNull($booking->status_changed_at);
        $this->assertTrue($booking->isConfirmed());

        $this->patch(route('admin.bookings.update', $booking), ['status' => 'approved-by-hacker'])->assertSessionHasErrors('status');
        $this->assertSame(BookingRequest::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    public function test_status_update_cannot_change_booking_details(): void
    {
        $booking = $this->request();

        $this->actingAs($this->admin())->patch(route('admin.bookings.update', $booking), [
            'status' => 'contacted', 'fare_amount' => '1', 'customer_phone' => '0000000000', 'reference' => 'X',
        ]);

        $booking->refresh();
        $this->assertSame(BookingRequest::STATUS_CONTACTED, $booking->status);
        $this->assertNull($booking->fare_amount);
        $this->assertSame('9876512345', $booking->customer_phone);
        $this->assertNotSame('X', $booking->reference);
    }
}
