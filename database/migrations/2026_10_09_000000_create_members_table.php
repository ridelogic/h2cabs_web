<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public profiles of association members shown on the About page.
 * Only fields the association approves for publication belong here;
 * private membership records are a separate, future module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedSmallInteger('member_since')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
