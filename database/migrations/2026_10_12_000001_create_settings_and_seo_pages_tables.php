<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable website settings (contact details, WhatsApp, app links,
 * social links, logo) and per-page SEO overrides. Both are additive: an
 * empty table means the site behaves exactly as configured in .env and code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('route_name', 100)->unique();
            $table->string('title', 120)->nullable();
            $table->string('description', 320)->nullable();
            $table->boolean('indexable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
        Schema::dropIfExists('settings');
    }
};
