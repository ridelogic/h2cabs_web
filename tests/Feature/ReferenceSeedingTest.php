<?php

namespace Tests\Feature;

use App\Enums\BookingType;
use App\Models\FareRule;
use App\Models\Member;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReferenceMembersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_the_fifteen_reference_members_exactly(): void
    {
        $this->seed(ReferenceMembersSeeder::class);

        $this->assertSame(15, Member::count());
        $this->assertSame(
            ['G. Prabaharan', 'H. Ashraff Ali', 'K. Yuvaraj', 'M. Venkadesan', 'S. Ramesh', 'K. Raguraman', 'K. Radhakrishnan',
                'P. Kumar', 'E. Pichande', 'P. Senthil', 'T. Suresh', 'J. Imran Basha', 'G. Manikandan', 'K. Abdul Samud', 'S. Arun Kumar'],
            Member::query()->ordered()->pluck('name')->all(),
        );

        $president = Member::where('name', 'G. Prabaharan')->first();
        $this->assertSame('President', $president->role);
        $this->assertSame('office_bearer', $president->member_group);
        $this->assertSame(8, Member::where('member_group', 'executive')->count());

        // Nothing beyond the published names and roles is invented.
        $this->assertSame(0, Member::whereNotNull('photo_path')->orWhereNotNull('phone')->orWhereNotNull('bio')->count());
    }

    public function test_reseeding_does_not_duplicate_overwrite_or_resurrect(): void
    {
        $this->seed(ReferenceMembersSeeder::class);

        $edited = Member::where('name', 'K. Yuvaraj')->first();
        $edited->update(['role' => 'General Secretary', 'is_published' => false]);
        Member::where('name', 'P. Kumar')->first()->delete();

        $this->seed(ReferenceMembersSeeder::class);
        $this->seed(ReferenceMembersSeeder::class);

        $this->assertSame(14, Member::count());
        $this->assertSame(15, Member::withTrashed()->count());
        $this->assertSame('General Secretary', $edited->fresh()->role);
        $this->assertFalse($edited->fresh()->is_published);
    }

    public function test_seeds_sedan_and_suv_with_proposed_fares_per_booking_type(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Vehicle::count());
        $this->assertSame(0, User::count(), 'No users (and no default passwords) may be seeded.');

        $sedan = Vehicle::where('slug', 'sedan')->with('fareRules')->first();
        $suv = Vehicle::where('slug', 'suv')->with('fareRules')->first();

        foreach ([BookingType::Local, BookingType::OutstationOneWay, BookingType::OutstationRoundTrip] as $type) {
            $this->assertSame('13.00', $sedan->configuredFareFor($type)->rate_per_km);
            $this->assertSame('14.00', $suv->configuredFareFor($type)->rate_per_km);
            $this->assertSame('13.0', $sedan->configuredFareFor($type)->min_billable_km);
            $this->assertFalse($sedan->configuredFareFor($type)->is_approved, 'Seeded fares are proposals, not approved tariffs.');
            // Proposals are never offered to customers until an admin approves them.
            $this->assertNull($sedan->fareFor($type));
            $this->assertFalse(Vehicle::query()->bookableFor($type)->exists());
        }

        // Rental has its own rule and does not inherit any per-km pricing.
        $rental = $sedan->configuredFareFor(BookingType::Rental);
        $this->assertNotNull($rental);
        $this->assertNull($rental->rate_per_km);
        $this->assertNull($rental->min_billable_km);
        $this->assertSame(0, $rental->packages()->count());
        $this->assertSame(16, FareRule::count());

        $this->assertSame(FareRule::RETURN_BOTH_WAYS, $sedan->configuredFareFor(BookingType::OutstationRoundTrip)->round_trip_distance);
        $this->assertFalse(Vehicle::where('slug', 'premium')->first()->is_active);
    }

    public function test_reseeding_keeps_admin_fare_changes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $fare = Vehicle::where('slug', 'sedan')->first()->fareRules()->where('booking_type', 'local')->first();
        $fare->update(['rate_per_km' => '15.50', 'is_approved' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame('15.50', $fare->fresh()->rate_per_km);
        $this->assertTrue($fare->fresh()->is_approved);
    }
}
