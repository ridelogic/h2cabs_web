<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Safe to run on every deployment (`php artisan db:seed --force`): every
 * seeder here is idempotent and never overwrites admin edits.
 *
 * No users are seeded. Create admins with `php artisan h2cabs:create-admin`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceMembersSeeder::class,
            VehicleFareSeeder::class,
        ]);
    }
}
