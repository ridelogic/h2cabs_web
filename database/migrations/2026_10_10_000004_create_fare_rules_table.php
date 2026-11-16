<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One fare rule per vehicle and booking type. Money is stored as DECIMAL
 * and calculated in integer paise (see App\Fares\Money) — never floats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fare_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('booking_type', 32);

            $table->decimal('rate_per_km', 10, 2);
            $table->decimal('min_billable_km', 8, 1)->nullable();
            $table->decimal('min_km_per_day', 8, 1)->nullable();
            $table->decimal('minimum_fare', 10, 2)->nullable();
            $table->decimal('driver_allowance_per_day', 10, 2)->nullable();
            $table->decimal('waiting_charge_per_hour', 10, 2)->nullable();
            $table->decimal('extra_charge', 10, 2)->nullable();
            $table->string('extra_charge_label', 80)->nullable();
            // included | extra — whether tolls/parking are part of the fare
            $table->string('tolls_parking', 16)->default('extra');
            // Round trips only: how the return journey is billed (both_ways | one_way_only)
            $table->string('round_trip_distance', 16)->nullable();

            // Proposed rates are shown as indicative until an admin marks them approved.
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'booking_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_rules');
    }
};
