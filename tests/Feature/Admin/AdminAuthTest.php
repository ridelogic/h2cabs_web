<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'members' => ['/admin/members'],
            'member create' => ['/admin/members/create'],
            'vehicles' => ['/admin/vehicles'],
            'fares' => ['/admin/fares'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_guests_are_sent_to_login(string $url): void
    {
        $this->get($url)->assertRedirect(route('admin.login'));
    }

    #[DataProvider('adminPages')]
    public function test_non_admin_users_are_forbidden(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    #[DataProvider('adminPages')]
    public function test_admins_can_open_admin_pages(string $url): void
    {
        $this->actingAs($this->admin())->get($url)->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_admin_can_log_in_and_out(): void
    {
        $admin = $this->admin(['email' => 'admin@example.com', 'password' => 'correct-horse-battery']);

        $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'correct-horse-battery'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_non_admin_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => 'correct-horse-battery']);

        $this->post(route('admin.login.store'), ['email' => 'user@example.com', 'password' => 'correct-horse-battery'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->admin(['email' => 'admin@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'wrong-password']);
        }

        $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Too many login attempts.', session('errors')->first('email'));
    }

    public function test_admin_flag_cannot_be_mass_assigned(): void
    {
        $user = new User(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-secret', 'is_admin' => true]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_create_admin_command_creates_an_admin(): void
    {
        $this->artisan('h2cabs:create-admin', ['email' => 'Owner@Example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password (min. 10 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-strong-password')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'owner@example.com')->first()->isAdmin());
    }

    public function test_create_admin_command_rejects_weak_passwords(): void
    {
        $this->artisan('h2cabs:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password (min. 10 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }
}
