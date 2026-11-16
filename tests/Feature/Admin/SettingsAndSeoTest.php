<?php

namespace Tests\Feature\Admin;

use App\Models\SeoPage;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsAndSeoTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function settings(array $overrides = []): array
    {
        return array_merge([
            'contact__phone' => '', 'contact__whatsapp' => '', 'contact__email' => '', 'contact__address' => '', 'contact__hours' => '',
            'apps__passenger__android' => '', 'apps__passenger__ios' => '', 'apps__driver__android' => '', 'apps__driver__ios' => '',
            'social__facebook' => '', 'social__instagram' => '', 'social__youtube' => '',
        ], $overrides);
    }

    // --- Website settings ------------------------------------------------------

    public function test_guests_and_non_admins_cannot_view_or_change_settings_or_seo(): void
    {
        foreach (['admin.settings.edit', 'admin.seo.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }
        $this->put(route('admin.settings.update'), $this->settings(['contact__phone' => '+91 90000 00000']))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.settings.edit'))->assertForbidden();
        $this->put(route('admin.settings.update'), $this->settings(['contact__phone' => '+91 90000 00000']))->assertForbidden();
        $this->get(route('admin.seo.edit', 'about'))->assertForbidden();
        $this->put(route('admin.seo.update', 'about'), ['title' => 'Hacked'])->assertForbidden();

        $this->assertSame(0, Setting::count());
        $this->assertSame(0, SeoPage::count());
    }

    public function test_saved_contact_and_whatsapp_settings_persist_and_appear_on_the_website(): void
    {
        config(['site.contact.whatsapp' => null, 'site.contact.phone' => null]);

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->settings([
                'contact__phone' => '+91 90000 11111',
                'contact__whatsapp' => '90000 22222',
                'contact__email' => 'bookings@example.org',
            ]))
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'contact.whatsapp', 'value' => '90000 22222']);

        // The next request applies saved settings at boot; simulate that on this database.
        SiteSettings::apply();
        $this->get(route('home'))
            ->assertSee('href="tel:+919000011111"', false)
            ->assertSee('https://wa.me/919000022222', false)
            ->assertSee('bookings@example.org');
    }

    public function test_blank_setting_falls_back_to_the_env_default(): void
    {
        Setting::put('contact.phone', '+91 90000 11111');
        Setting::put('contact.phone', '');

        $this->assertSame(0, Setting::where('key', 'contact.phone')->count());
        $this->actingAs($this->admin())->get(route('admin.settings.edit'))->assertOk()->assertSee('Not set');
    }

    public function test_settings_are_validated(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->settings([
                'contact__phone' => 'call me',
                'contact__whatsapp' => '12345',
                'contact__email' => 'not-an-email',
                'apps__passenger__android' => 'http://play.google.com/store/apps/details?id=x',
                'apps__passenger__ios' => 'https://example.com/fake-app',
                'social__facebook' => 'javascript:alert(1)',
            ]))
            ->assertSessionHasErrors(['contact__phone', 'contact__whatsapp', 'contact__email', 'apps__passenger__android', 'apps__passenger__ios', 'social__facebook']);

        $this->assertSame(0, Setting::count());
    }

    public function test_real_store_links_enable_the_app_buttons_and_qr_codes(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->settings(['apps__passenger__android' => 'https://play.google.com/store/apps/details?id=org.example.h2cabs']))
            ->assertSessionHasNoErrors();

        SiteSettings::apply();
        $this->get(route('home'))->assertSee('Get it on Google Play')->assertSee('QR code to download the H2 CABS app from Google Play');
    }

    public function test_logo_upload_is_validated_stored_and_replaced_safely(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->put(route('admin.settings.update'), $this->settings(['logo' => $svg]))->assertSessionHasErrors('logo');

        $this->put(route('admin.settings.update'), $this->settings(['logo' => UploadedFile::fake()->image('mark.png', 256, 256)]))->assertSessionHasNoErrors();
        $first = Setting::values()['brand.logo_mark'];
        $this->assertStringStartsWith('storage/branding/', $first);
        Storage::disk('public')->assertExists(substr($first, 8));

        $this->put(route('admin.settings.update'), $this->settings(['logo' => UploadedFile::fake()->image('mark2.png', 256, 256)]));
        $second = Setting::values()['brand.logo_mark'];
        Storage::disk('public')->assertMissing(substr($first, 8));

        SiteSettings::apply();
        $this->get(route('home'))->assertSee(asset($second), false);

        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->settings(['remove_logo' => '1']));
        $this->assertArrayNotHasKey('brand.logo_mark', Setting::values());
        Storage::disk('public')->assertMissing(substr($second, 8));
    }

    // --- SEO ---------------------------------------------------------------------

    public function test_seo_index_lists_every_public_page(): void
    {
        $this->actingAs($this->admin())->get(route('admin.seo.index'))
            ->assertOk()
            ->assertSee(['Home', 'About Us', 'Our Services', 'Destinations', 'Booking', 'Contact Us', 'Photo credits'])
            ->assertSee('Taxi Services in Pondicherry | H2 CABS');
    }

    public function test_seo_override_persists_and_changes_the_public_page(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.seo.update', 'services'), [
                'title' => 'Pondicherry Taxi & Outstation Cabs | H2 CABS',
                'description' => 'Book local rides, rentals and outstation trips from Puducherry.',
                'indexable' => '1',
            ])
            ->assertRedirect(route('admin.seo.edit', 'services'));

        $this->assertDatabaseHas('seo_pages', ['route_name' => 'services', 'title' => 'Pondicherry Taxi & Outstation Cabs | H2 CABS']);

        $this->get(route('services'))
            ->assertSee('<title>Pondicherry Taxi &amp; Outstation Cabs | H2 CABS</title>', false)
            ->assertSee('<meta name="description" content="Book local rides, rentals and outstation trips from Puducherry.">', false)
            ->assertSee('<meta property="og:title" content="Pondicherry Taxi &amp; Outstation Cabs | H2 CABS">', false);

        // Other pages are unaffected.
        $this->get(route('about'))->assertSee('<title>About the Helping Hand Drivers Association | H2 CABS</title>', false);
    }

    public function test_pages_with_dotted_route_names_keep_defaults_and_accept_overrides(): void
    {
        // Regression: "booking.create" must not be read as nested config keys.
        $this->get(route('booking.create'))->assertSee('<title>Book a Taxi in Pondicherry | H2 CABS</title>', false);

        $this->actingAs($this->admin())->get(route('admin.seo.edit', 'booking.create'))->assertOk()->assertSee('Book a Taxi in Pondicherry | H2 CABS');
        $this->put(route('admin.seo.update', 'booking.create'), ['title' => 'Book a Cab in Puducherry | H2 CABS', 'description' => '', 'indexable' => '1'])
            ->assertSessionHasNoErrors();

        $this->get(route('booking.create'))->assertSee('<title>Book a Cab in Puducherry | H2 CABS</title>', false)
            ->assertSee('Book a local ride, rental, outstation one-way or round-trip taxi'); // default description kept
    }

    public function test_clearing_an_override_restores_the_default(): void
    {
        SeoPage::create(['route_name' => 'contact', 'title' => 'Custom', 'description' => 'Custom', 'indexable' => true]);

        $this->actingAs($this->admin())->put(route('admin.seo.update', 'contact'), ['title' => '', 'description' => '', 'indexable' => '1']);

        $this->assertSame(0, SeoPage::count());
        $this->get(route('contact'))->assertSee('<title>Contact Us | H2 CABS</title>', false);
    }

    public function test_hiding_a_page_from_search_adds_noindex_and_removes_it_from_the_sitemap(): void
    {
        config(['site.seo.indexable' => true]);

        $this->actingAs($this->admin())->put(route('admin.seo.update', 'photo-credits'), ['title' => '', 'description' => '', 'indexable' => '0']);

        $this->get(route('photo-credits'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('sitemap'))->assertDontSee(route('photo-credits'))->assertSee('<loc>'.route('about').'</loc>', false);
        $this->get(route('about'))->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_seo_input_is_validated_and_unknown_pages_are_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.seo.update', 'about'), ['title' => str_repeat('a', 71)])->assertSessionHasErrors('title');
        $this->put(route('admin.seo.update', 'about'), ['description' => str_repeat('a', 301)])->assertSessionHasErrors('description');
        $this->get(route('admin.seo.edit', 'admin.dashboard'))->assertNotFound();
        $this->put(route('admin.seo.update', 'booking.confirmation'), ['title' => 'x'])->assertNotFound();
    }

    public function test_seo_values_are_escaped(): void
    {
        SeoPage::create(['route_name' => 'about', 'title' => '"><script>alert(1)</script>', 'description' => '<b>x</b>', 'indexable' => true]);

        $this->get(route('about'))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }
}
