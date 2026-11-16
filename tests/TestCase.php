<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests assert on HTML, not compiled assets, so they must
        // not depend on `npm run build` having produced a Vite manifest.
        $this->withoutVite();
    }

    /** An admin user (is_admin is not mass-assignable, so it is set explicitly). */
    protected function admin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }
}
