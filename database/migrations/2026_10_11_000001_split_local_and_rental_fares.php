<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Local and Rental are separate booking types.
 *
 * - Existing "local_rental" fare rules were per-km point-to-point fares, so
 *   they become "local" rules unchanged.
 * - Each vehicle gets its own "rental" rule with no rate (fare to be
 *   confirmed) — rental pricing is never copied from local or outstation.
 * - rate_per_km becomes nullable (a rental rule may have no extra-km rate),
 *   and rentals get an extra-hour rate.
 * - rental_packages holds priced packages (e.g. 4 hrs / 40 km) per rental rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fare_rules', function (Blueprint $table) {
            $table->decimal('rate_per_km', 10, 2)->nullable()->change();
            $table->decimal('extra_hour_rate', 10, 2)->nullable()->after('waiting_charge_per_hour');
        });

        Schema::create('rental_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fare_rule_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->decimal('hours', 5, 1);
            $table->decimal('included_km', 8, 1)->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('fare_rules')->where('booking_type', 'local_rental')->update(['booking_type' => 'local']);

        $now = now();
        foreach (DB::table('vehicles')->pluck('is_active', 'id') as $vehicleId => $active) {
            $exists = DB::table('fare_rules')->where('vehicle_id', $vehicleId)->where('booking_type', 'rental')->exists();

            if (! $exists) {
                DB::table('fare_rules')->insert([
                    'vehicle_id' => $vehicleId,
                    'booking_type' => 'rental',
                    'rate_per_km' => null,
                    'tolls_parking' => 'extra',
                    'is_approved' => false,
                    'is_active' => (bool) $active,
                    'notes' => 'Rental pricing not configured yet — add rental packages. Customers see "fare to be confirmed".',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_packages');
        DB::table('fare_rules')->where('booking_type', 'rental')->delete();
        DB::table('fare_rules')->where('booking_type', 'local')->update(['booking_type' => 'local_rental']);

        Schema::table('fare_rules', function (Blueprint $table) {
            $table->dropColumn('extra_hour_rate');
        });
    }
};
