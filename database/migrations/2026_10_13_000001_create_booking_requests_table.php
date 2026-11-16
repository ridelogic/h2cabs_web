<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking requests submitted through the website. A row is a REQUEST, not
 * a confirmed booking: status stays "new" until an admin changes it.
 * Vehicle and package names are snapshotted so the record stays readable
 * if the vehicle or package is later edited or deleted.
 *
 * Additive only: creates one new table and touches nothing else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            // One row per submitted form (double-click / resubmit protection).
            $table->uuid('form_token')->nullable()->unique();
            $table->string('booking_type', 30)->index();
            $table->string('status', 20)->default('new')->index();

            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_name', 120)->nullable();
            $table->foreignId('rental_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rental_package_label', 200)->nullable();
            $table->string('rental_duration', 30)->nullable();
            $table->json('sightseeing')->nullable();

            $table->string('pickup', 200);
            $table->string('pickup_place_id', 300)->nullable();
            $table->string('dropoff', 200)->nullable();
            $table->string('dropoff_place_id', 300)->nullable();

            $table->date('travel_date')->index();
            $table->string('pickup_time', 5);
            $table->date('return_date')->nullable();
            $table->string('return_time', 5)->nullable();

            $table->unsignedTinyInteger('passengers');
            $table->string('customer_name', 80);
            $table->string('customer_phone', 10)->index();
            $table->text('notes')->nullable();

            // Server-calculated only. fare_amount is set only for an
            // estimate from an APPROVED fare rule.
            $table->unsignedInteger('distance_meters')->nullable();
            $table->decimal('fare_amount', 10, 2)->nullable();
            $table->string('fare_status', 30);
            $table->string('fare_summary', 200)->nullable();
            $table->json('fare_notes')->nullable();

            // not_configured | link_shown | link_opened — the site cannot know
            // whether the customer actually pressed Send in WhatsApp.
            $table->string('whatsapp_status', 20);
            $table->timestamp('whatsapp_opened_at')->nullable();

            $table->text('admin_notes')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
