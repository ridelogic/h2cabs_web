<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds admin-managed fields to members:
 * - is_active: membership status (inactive members never appear publicly)
 * - is_published (existing): show/hide on the public website
 * - member_group: office bearer or executive member, for grouping on the About page
 * - phone: optional public contact number, only if the member approves publication
 * - source_key: stable key for records imported from the reference website,
 *   so the seeder is idempotent and never duplicates or overwrites them
 * - soft deletes: a deleted imported member is not re-created by a later seed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('member_since');
            $table->string('member_group', 32)->default('member')->after('role');
            $table->string('phone', 20)->nullable()->after('photo_path');
            $table->string('source_key', 100)->nullable()->unique()->after('sort_order');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['source_key']);
            $table->dropSoftDeletes();
            $table->dropColumn(['is_active', 'member_group', 'phone', 'source_key']);
        });
    }
};
