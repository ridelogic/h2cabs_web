<?php

namespace App\Content;

use App\Enums\BookingType;
use App\Enums\RentalDuration;

/**
 * Services shown on the website. The four booking types (Local, Rental,
 * Outstation One Way, Outstation Round Trip) are the primary services; the
 * remaining entries are common uses of those types, taken from the
 * reference website. "Book Now" links preselect the matching type.
 *
 * `photo` is a key in config/photos.php.
 */
class ServiceCatalog
{
    /** @return list<array{key: string, title: string, summary: string, points: list<string>, photo: string, icon: string, type: BookingType, duration: ?RentalDuration, primary: bool}> */
    public function all(): array
    {
        return [
            [
                'key' => 'local',
                'title' => 'Local',
                'summary' => 'Point-to-point rides within Puducherry — to work, the hospital, the market, the railway station or a friend\'s home.',
                'points' => ['Pickup and drop anywhere in the local area', 'Puducherry town, White Town, Lawspet, Ariyankuppam and nearby', 'Per-km fare estimate before you book'],
                'photo' => 'white-town',
                'icon' => 'pin',
                'type' => BookingType::Local,
                'duration' => null,
                'primary' => true,
            ],
            [
                'key' => 'rental',
                'title' => 'Rental',
                'summary' => 'A car and driver at your disposal for a few hours, half a day, a full day or several days.',
                'points' => ['Hourly, half-day, full-day or multi-day', 'Driver stays with you for the booked time', 'Ideal for sightseeing, meetings and events'],
                'photo' => 'basilica',
                'icon' => 'clock',
                'type' => BookingType::Rental,
                'duration' => null,
                'primary' => true,
            ],
            [
                'key' => 'outstation-one-way',
                'title' => 'Outstation One Way',
                'summary' => 'A one-way drop from Puducherry to another town or city, with no return journey to arrange.',
                'points' => ['Chennai, Mahabalipuram, Tiruvannamalai and more', 'Distance-based estimate when available', 'Sedan or SUV'],
                'photo' => 'highway',
                'icon' => 'arrow-right',
                'type' => BookingType::OutstationOneWay,
                'duration' => null,
                'primary' => true,
            ],
            [
                'key' => 'outstation-round-trip',
                'title' => 'Outstation Round Trip',
                'summary' => 'Book the onward and return journey together, whether you come back the same day or a few days later.',
                'points' => ['Departure and return dates', 'Same car for the whole trip', 'Pilgrimages, family visits and events'],
                'photo' => 'village-road',
                'icon' => 'repeat',
                'type' => BookingType::OutstationRoundTrip,
                'duration' => null,
                'primary' => true,
            ],
            [
                'key' => 'sightseeing',
                'title' => 'Sightseeing',
                'summary' => 'Explore Pondicherry\'s beaches, French Quarter, Auroville and temples on a rental with a driver who knows the town.',
                'points' => ['Pick the places you want to see', 'Half-day or full-day rental'],
                'photo' => 'auroville',
                'icon' => 'camera',
                'type' => BookingType::Rental,
                'duration' => RentalDuration::HalfDay,
                'primary' => false,
            ],
            [
                'key' => 'airport-railway',
                'title' => 'Airport & Railway Transfers',
                'summary' => 'Local pickups and drops for Puducherry railway station and airport. Chennai airport trips are booked as Outstation One Way.',
                'points' => ['Add your train or flight number in the notes'],
                'photo' => 'airport',
                'icon' => 'plane',
                'type' => BookingType::Local,
                'duration' => null,
                'primary' => false,
            ],
            [
                'key' => 'events-business',
                'title' => 'Weddings, Events & Business',
                'summary' => 'Rental cabs for weddings, family functions, meetings and corporate travel, with the car on standby.',
                'points' => ['Full-day or multi-day rental'],
                'photo' => 'sunrise',
                'icon' => 'calendar',
                'type' => BookingType::Rental,
                'duration' => RentalDuration::FullDay,
                'primary' => false,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function primary(): array
    {
        return array_values(array_filter($this->all(), fn (array $service) => $service['primary']));
    }

    /** @return list<array<string, mixed>> */
    public function featured(): array
    {
        return $this->primary();
    }

    /** @return list<array<string, mixed>> */
    public function uses(): array
    {
        return array_values(array_filter($this->all(), fn (array $service) => ! $service['primary']));
    }

    /**
     * Booking-page query for a service's "Book Now" link.
     *
     * @param  array<string, mixed>  $service
     * @return array<string, string>
     */
    public static function bookingParams(array $service): array
    {
        return array_filter([
            'type' => $service['type']->value,
            'duration' => $service['duration']?->value,
        ]);
    }

    /**
     * Frequently travelled outstation destinations listed on the reference
     * website. Examples only — no distances, prices or availability implied.
     *
     * @return list<string>
     */
    public function outstationRoutes(): array
    {
        return ['Chennai', 'Mahabalipuram', 'Tiruvannamalai', 'Chidambaram', 'Cuddalore', 'Villupuram'];
    }
}
