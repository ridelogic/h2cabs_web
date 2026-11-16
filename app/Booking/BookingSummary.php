<?php

namespace App\Booking;

use App\Content\DestinationCatalog;
use App\Enums\BookingType;
use App\Models\BookingRequest;
use Illuminate\Support\Carbon;

/**
 * Turns a stored booking request into a readable summary — labelled rows
 * for the review page and the plain-text WhatsApp message. Built from the
 * persisted record (never from the browser), so the review page, the
 * WhatsApp message and the admin view always say the same thing.
 */
class BookingSummary
{
    /**
     * @param  list<array{label: string, value: string}>  $rows
     * @param  list<string>  $fareNotes
     */
    private function __construct(
        public readonly string $reference,
        public readonly array $rows,
        public readonly array $fareNotes,
    ) {}

    public static function make(BookingRequest $booking, DestinationCatalog $destinations): self
    {
        $type = $booking->booking_type;
        $rows = [['Booking reference', $booking->reference], ['Booking type', $type->label()]];

        if ($booking->vehicle_name) {
            $rows[] = ['Vehicle', $booking->vehicle_name];
        }

        if ($type === BookingType::Rental) {
            $rows[] = $booking->rental_package_label
                ? ['Rental package', $booking->rental_package_label]
                : ['Rental duration', $booking->rental_duration?->label() ?? 'To be agreed'];
        }

        $rows[] = ['Pickup', self::location($booking->pickup, $booking->pickup_place_id)];

        if (filled($booking->dropoff)) {
            $rows[] = [$type->isOutstation() ? 'Destination' : 'Drop-off', self::location($booking->dropoff, $booking->dropoff_place_id)];
        }

        if ($type === BookingType::Rental && filled($booking->sightseeing)) {
            $names = collect($booking->sightseeing)->map(fn (string $slug) => $destinations->find($slug)['name'] ?? null)->filter();
            $rows[] = ['Places to visit', $names->implode(', ')];
        }

        $rows[] = [$type->isOutstation() ? 'Departure date' : 'Pickup date', $booking->travel_date->format('D, j M Y')];
        $rows[] = ['Pickup time', self::time($booking->pickup_time)];

        if ($type === BookingType::OutstationRoundTrip && $booking->return_date) {
            $return = $booking->return_date->format('D, j M Y');
            $rows[] = ['Return', filled($booking->return_time) ? $return.', '.self::time($booking->return_time) : $return];
        }

        if ($distance = BookingQuote::formatDistance($booking->distance_meters, $type)) {
            $rows[] = ['Estimated distance', $distance];
        }

        $rows[] = ['Fare', $booking->fare_summary ?: 'Fare will be confirmed by H2 CABS'];
        $rows[] = ['Passengers', (string) $booking->passengers];
        $rows[] = ['Name', $booking->customer_name];
        $rows[] = ['Mobile', $booking->customer_phone];

        if (filled($booking->notes)) {
            $rows[] = ['Notes', $booking->notes];
        }

        return new self(
            $booking->reference,
            array_map(fn (array $row) => ['label' => $row[0], 'value' => self::clean($row[1], $row[0] === 'Notes')], $rows),
            $booking->fare_notes ?? [],
        );
    }

    /** Plain text; App\Support\WhatsApp::chatUrl() encodes it for wa.me. */
    public function toWhatsAppMessage(): string
    {
        $lines = ['Hello H2 CABS, I would like to book a ride.', ''];

        foreach ($this->rows as $row) {
            $lines[] = $row['label'].': '.$row['value'];
        }

        $lines[] = '';
        $lines[] = 'This is a booking request. Please confirm availability and the final fare.';

        return implode("\n", $lines);
    }

    private static function location(string $text, ?string $placeId): string
    {
        // A Maps link lets the driver open the exact place chosen from search.
        return $placeId
            ? $text.' (https://www.google.com/maps/place/?q=place_id:'.$placeId.')'
            : $text;
    }

    private static function time(string $value): string
    {
        return Carbon::createFromFormat('H:i', $value)->format('g:i A');
    }

    /** Collapse whitespace so user input cannot break the message layout. */
    private static function clean(string $value, bool $multiline): string
    {
        if ($multiline) {
            $value = preg_replace("/[ \t]+/", ' ', str_replace("\r", '', $value));

            return trim(preg_replace("/\n{2,}/", "\n", $value));
        }

        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
