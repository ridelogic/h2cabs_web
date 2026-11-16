<?php

namespace App\Booking;

use App\Enums\BookingType;
use App\Fares\FareEstimate;
use App\Models\BookingRequest;
use App\Support\WhatsApp;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Stores a validated booking request with its server-side quote.
 *
 * Every value comes from StoreBookingRequest::validated() or from the
 * server's own quote; fare amounts and statuses posted by a browser are
 * never accepted. New requests always start as "new" — only an admin can
 * mark a request confirmed.
 */
class BookingRecorder
{
    /** Identical submissions without a form token within this window are treated as one. */
    private const DUPLICATE_WINDOW_SECONDS = 120;

    public function __construct(private readonly BookingQuoter $quoter) {}

    /** @param  array<string, mixed>  $data  Output of StoreBookingRequest::validated() */
    public function record(array $data): BookingRequest
    {
        $token = $data['form_token'] ?? null;

        if ($existing = $this->duplicateOf($data, $token)) {
            return $existing;
        }

        $quote = $this->quoter->quote($data);
        $type = BookingType::from($data['booking_type']);
        $estimate = $quote->estimate;

        $booking = new BookingRequest;
        $booking->forceFill([
            'reference' => BookingRequest::newReference(),
            'form_token' => $token,
            'booking_type' => $type,
            'status' => BookingRequest::STATUS_NEW,
            'vehicle_id' => $quote->vehicle?->id,
            'vehicle_name' => $quote->vehicle?->name,
            'rental_package_id' => $type === BookingType::Rental ? $quote->package?->id : null,
            'rental_package_label' => $type === BookingType::Rental ? $quote->package?->publicSummary() : null,
            'rental_duration' => $type === BookingType::Rental && ! $quote->package ? ($data['rental_duration'] ?? null) : null,
            'sightseeing' => $type === BookingType::Rental && filled($data['sightseeing'] ?? null) ? array_values($data['sightseeing']) : null,
            'pickup' => $data['pickup'],
            'pickup_place_id' => $data['pickup_place_id'] ?? null,
            'dropoff' => filled($data['dropoff'] ?? null) ? $data['dropoff'] : null,
            'dropoff_place_id' => filled($data['dropoff'] ?? null) ? ($data['dropoff_place_id'] ?? null) : null,
            'travel_date' => $data['travel_date'],
            'pickup_time' => $data['pickup_time'],
            'return_date' => $type === BookingType::OutstationRoundTrip ? $data['return_date'] : null,
            'return_time' => $type === BookingType::OutstationRoundTrip ? ($data['return_time'] ?? null) : null,
            'passengers' => (int) $data['passengers'],
            'customer_name' => $data['name'],
            'customer_phone' => $data['phone'],
            'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
            'distance_meters' => $quote->distanceMeters,
            // An amount is stored only for an estimate from an APPROVED rate.
            'fare_amount' => $estimate?->isEstimated() ? self::rupees($estimate->total) : null,
            'fare_status' => $estimate?->status() ?? FareEstimate::REQUIRES_CONFIRMATION,
            'fare_summary' => $estimate?->summaryLine() ?? 'Fare will be confirmed by H2 CABS',
            'fare_notes' => $this->fareNotes($estimate) ?: null,
            'whatsapp_status' => WhatsApp::isConfigured() ? BookingRequest::WHATSAPP_LINK_SHOWN : BookingRequest::WHATSAPP_NOT_CONFIGURED,
        ]);

        try {
            $booking->save();
        } catch (UniqueConstraintViolationException $e) {
            // Two simultaneous submissions of the same form: keep the first.
            if ($token && ($existing = BookingRequest::query()->where('form_token', $token)->first())) {
                return $existing;
            }

            throw $e;
        }

        return $booking;
    }

    /** @param  array<string, mixed>  $data */
    private function duplicateOf(array $data, ?string $token): ?BookingRequest
    {
        if ($token) {
            return BookingRequest::query()->where('form_token', $token)->first();
        }

        return BookingRequest::query()
            ->whereNull('form_token')
            ->where('customer_phone', $data['phone'])
            ->where('booking_type', $data['booking_type'])
            ->whereDate('travel_date', $data['travel_date'])
            ->where('pickup_time', $data['pickup_time'])
            ->where('pickup', $data['pickup'])
            ->where('created_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
            ->latest('id')
            ->first();
    }

    /** @return list<string> */
    private function fareNotes(?FareEstimate $estimate): array
    {
        if (! $estimate) {
            return [];
        }

        $public = $estimate->toArray();

        return array_values([...$public['details'], ...$public['notes']]);
    }

    private static function rupees(int $paise): string
    {
        return sprintf('%d.%02d', intdiv($paise, 100), $paise % 100);
    }
}
