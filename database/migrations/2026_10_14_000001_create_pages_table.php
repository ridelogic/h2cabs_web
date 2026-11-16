<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom website pages managed in Admin → Website Pages and served at
 * /{slug} when published. The built-in pages (Home, About, ...) are not
 * stored here and are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 80)->unique();
            $table->string('page_type', 20)->default('standard');
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->longText('content');
            $table->string('featured_image_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            // Optional link in the public footer or main navigation.
            $table->string('nav_placement', 10)->default('none');
            $table->unsignedInteger('nav_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'nav_placement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
