<?php

namespace App\Http\Controllers;

use App\Booking\BookingFormData;
use App\Booking\BookingQuoter;
use App\Booking\BookingRecorder;
use App\Booking\BookingSummary;
use App\Content\DestinationCatalog;
use App\Enums\BookingType;
use App\Enums\RentalDuration;
use App\Http\Requests\StoreBookingRequest;
use App\Models\BookingRequest;
use App\Models\Vehicle;
use App\Places\RouteDistance;
use App\Support\SeoMeta;
use App\Support\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Booking requests: the site validates the form, calculates any estimate
 * on the server, stores the request with a reference (status "new") and
 * builds a wa.me link from the stored record. The customer must still
 * press Send in WhatsApp. Nothing is "confirmed" until an admin does so.
 */
class BookingController extends Controller
{
    private const REQUEST_KEY = 'booking.request_id';

    private const INPUT_KEY = 'booking.input';

    public function create(Request $request, BookingFormData $form, RouteDistance $distance): View
    {
        return view('pages.booking.create', [
            'seo' => SeoMeta::page('booking.create'),
            'booking' => $form->make($request),
            'distanceEnabled' => $distance->available(),
        ]);
    }

    public function store(StoreBookingRequest $request, BookingRecorder $recorder): RedirectResponse
    {
        // Idempotent: a double-click or resubmitted form (same form_token)
        // returns the request already stored instead of creating another.
        $booking = $recorder->record($request->validated());

        $request->session()->put(self::REQUEST_KEY, $booking->id);
        $request->session()->put(self::INPUT_KEY, $request->safe()->except('form_token'));

        return redirect()->route('booking.confirmation');
    }

    /**
     * Live estimate for the booking form. Recalculated entirely on the
     * server from the active fare rules; nothing price-related is accepted
     * from the browser.
     */
    public function estimate(Request $request, BookingQuoter $quoter): JsonResponse
    {
        // This background request must not consume flashed validation errors
        // or old input meant for the next page view.
        $request->session()->reflash();

        $data = $request->validate([
            'booking_type' => ['required', Rule::enum(BookingType::class)],
            'vehicle_id' => ['required', 'integer'],
            'rental_package_id' => ['nullable', 'integer'],
            'rental_duration' => ['nullable', Rule::enum(RentalDuration::class)],
            'pickup' => ['nullable', 'string', 'max:200'],
            'pickup_place_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{10,300}$/'],
            'dropoff' => ['nullable', 'string', 'max:200'],
            'dropoff_place_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{10,300}$/'],
            'travel_date' => ['nullable', 'date_format:Y-m-d'],
            'return_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:travel_date'],
        ]);

        $type = BookingType::from($data['booking_type']);

        if (! Vehicle::query()->bookableFor($type)->whereKey($data['vehicle_id'])->exists()) {
            return response()->json(['message' => 'This vehicle is not available for '.$type->label().'. Please choose another.'], 422);
        }

        return response()->json($quoter->quote($data)->toArray());
    }

    public function confirmation(Request $request, DestinationCatalog $destinations): View|RedirectResponse
    {
        // Only the session that submitted the request can view it here.
        $booking = $this->sessionBooking($request);

        if (! $booking) {
            return redirect()->route('booking.create');
        }

        $summary = BookingSummary::make($booking, $destinations);
        $message = $summary->toWhatsAppMessage();

        return view('pages.booking.confirmation', [
            'seo' => SeoMeta::make([
                'title' => 'Send Your Booking Request',
                'description' => 'Review your H2 CABS ride request and send it on WhatsApp.',
                'canonical' => route('booking.confirmation'),
                'indexable' => false,
            ]),
            'reference' => $booking->reference,
            'rows' => $summary->rows,
            'fareNotes' => $summary->fareNotes,
            'message' => $message,
            'whatsappUrl' => WhatsApp::chatUrl($message),
        ]);
    }

    /**
     * Records that the customer opened the WhatsApp link (sent by the
     * review page with navigator.sendBeacon). This does NOT mean the
     * message was sent — only WhatsApp knows that.
     */
    public function whatsappOpened(Request $request): Response
    {
        $booking = $this->sessionBooking($request);

        if ($booking && $booking->whatsapp_status === BookingRequest::WHATSAPP_LINK_SHOWN) {
            $booking->forceFill([
                'whatsapp_status' => BookingRequest::WHATSAPP_LINK_OPENED,
                'whatsapp_opened_at' => now(),
            ])->save();
        }

        return response()->noContent();
    }

    private function sessionBooking(Request $request): ?BookingRequest
    {
        $id = $request->session()->get(self::REQUEST_KEY);

        return $id ? BookingRequest::query()->find($id) : null;
    }
}
