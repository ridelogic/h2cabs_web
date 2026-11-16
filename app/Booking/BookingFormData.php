<?php

namespace App\Booking;

use App\Content\DestinationCatalog;
use App\Enums\BookingType;
use App\Models\RentalPackage;
use App\Models\Vehicle;
use App\Support\WhatsApp;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Everything the shared booking form (<x-booking.form>) needs, for both the
 * homepage widget and the /booking page, so both use identical booking
 * types, vehicles, rates and defaults. Rates and availability come from the
 * database — nothing is hardcoded in the browser.
 */
class BookingFormData
{
    private const INPUT_KEY = 'booking.input';

    /** Old ?trip= links still preselect a sensible booking type. */
    private const LEGACY = [
        'one_way' => BookingType::OutstationOneWay,
        'round_trip' => BookingType::OutstationRoundTrip,
        'local_sightseeing' => BookingType::Rental,
        'local_rental' => BookingType::Local,
        'airport_transfer' => BookingType::Local,
    ];

    public function __construct(private readonly DestinationCatalog $destinations) {}

    /**
     * @return array<string, mixed>
     */
    public function make(Request $request): array
    {
        $vehicles = $this->vehicles();
        $tz = config('site.booking.timezone');
        $requested = (string) $request->query('vehicle');

        return [
            'bookingTypes' => BookingType::cases(),
            'vehicles' => $vehicles,
            'vehicleOptions' => $this->vehicleOptions($vehicles),
            'destinations' => $this->destinations->all(),
            'defaults' => $this->defaults($request, $vehicles),
            'places' => $this->placesConfig(),
            'whatsappConfigured' => WhatsApp::isConfigured(),
            'formToken' => (string) Str::uuid(),
            'minDate' => Carbon::now($tz)->toDateString(),
            'maxDate' => Carbon::now($tz)->addYear()->toDateString(),
            // A ?vehicle= link to a vehicle that is not (or no longer) offered.
            'unavailableVehicle' => $requested !== '' && ! $vehicles->contains('slug', $requested),
        ];
    }

    /**
     * How the location fields search:
     * - "google": Maps JavaScript API in the browser (referrer-restricted browser key)
     * - "proxy":  server-side Places API through /places/search (server key)
     * - "unavailable": no Google key — location fields stay disabled and
     *   bookings cannot be submitted (there is no manual-entry fallback)
     *
     * @return array{mode: string, browserKey: ?string}
     */
    public function placesConfig(): array
    {
        $browserKey = config('services.google_maps.browser_key');
        $serverKeys = array_filter([config('services.google_places.key'), config('services.google_routes.key')]);

        // Safety net: a server key pasted into GOOGLE_MAPS_BROWSER_KEY would be
        // published in every page. Refuse to render it.
        if (filled($browserKey) && in_array($browserKey, $serverKeys, true)) {
            Log::warning('GOOGLE_MAPS_BROWSER_KEY matches a server-side Google key and was not sent to the browser. Use a separate, referrer-restricted browser key.');
            $browserKey = null;
        }

        return [
            'mode' => filled($browserKey) ? 'google' : (filled(config('services.google_places.key')) ? 'proxy' : 'unavailable'),
            'browserKey' => filled($browserKey) ? $browserKey : null,
        ];
    }

    /**
     * Active vehicles with their fare rules and packages. Empty if the tables
     * have not been migrated yet, so the page still renders.
     *
     * @return Collection<int, Vehicle>
     */
    private function vehicles(): Collection
    {
        try {
            return Vehicle::query()->active()->with('fareRules.packages')->get();
        } catch (QueryException $e) {
            Log::warning('Vehicles could not be loaded; run `php artisan migrate`.', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * Per vehicle and booking type: whether it is offered (an active,
     * approved and complete fare — see FareRule::bookingIssue()), its rate
     * summary and, for rentals, its active packages.
     *
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array<int, array<string, array<string, mixed>>>
     */
    private function vehicleOptions(Collection $vehicles): array
    {
        return $vehicles->mapWithKeys(fn (Vehicle $vehicle) => [
            $vehicle->id => collect(BookingType::cases())->mapWithKeys(function (BookingType $type) use ($vehicle) {
                $rule = $vehicle->fareFor($type);

                return [$type->value => $rule ? [
                    'available' => true,
                    'rate' => $rule->publicRateLabel(),
                    'approved' => $rule->is_approved,
                    'packages' => $rule->packages->where('is_active', true)
                        ->map(fn (RentalPackage $package) => ['id' => $package->id, 'label' => $package->summary($rule->is_approved)])
                        ->values()->all(),
                ] : ['available' => false]];
            })->all(),
        ])->all();
    }

    /**
     * Initial values: the customer's previous request when editing (or after
     * a validation error, via old()), otherwise safe prefills from links such
     * as /booking?type=outstation_one_way&vehicle=suv.
     *
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array<string, mixed>
     */
    private function defaults(Request $request, Collection $vehicles): array
    {
        if ($request->boolean('edit') && $request->session()->has(self::INPUT_KEY)) {
            return $request->session()->get(self::INPUT_KEY);
        }

        $selected = array_values(array_intersect(explode(',', (string) $request->query('destination')), $this->destinations->slugs()));

        $type = BookingType::tryFrom((string) $request->query('type'))
            ?? self::LEGACY[(string) $request->query('trip')]
            ?? ($selected ? BookingType::Rental : BookingType::Local);

        $vehicle = $vehicles->firstWhere('slug', (string) $request->query('vehicle'))
            ?? $vehicles->first(fn (Vehicle $v) => $v->fareFor($type));

        $date = (string) $request->query('date');
        $placeId = fn (string $key) => preg_match('/^[A-Za-z0-9_-]{10,300}$/', (string) $request->query($key)) ? $request->query($key) : null;

        return array_filter([
            'booking_type' => $type->value,
            'vehicle_id' => $vehicle?->id,
            'pickup' => Str::limit(trim((string) $request->query('pickup')), 200, ''),
            'pickup_place_id' => $placeId('pickup_place_id'),
            'dropoff' => Str::limit(trim((string) $request->query('dropoff')), 200, ''),
            'dropoff_place_id' => $placeId('dropoff_place_id'),
            'travel_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null,
            'sightseeing' => $selected ?: null,
            'passengers' => 1,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
