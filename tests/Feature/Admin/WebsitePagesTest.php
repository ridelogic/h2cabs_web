<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsitePagesTest extends TestCase
{
    use RefreshDatabase;

    private function page(array $attributes = []): Page
    {
        return Page::create([
            'title' => 'Corporate Travel',
            'slug' => 'corporate-travel',
            'page_type' => 'service',
            'content' => "## Travel for teams\n\nMonthly billing for companies in Puducherry.",
            'is_published' => true,
            'nav_placement' => 'none',
            ...$attributes,
        ]);
    }

    private function validInput(array $overrides = []): array
    {
        return [
            'title' => 'Airport Transfer',
            'slug' => '',
            'page_type' => 'service',
            'meta_title' => '',
            'meta_description' => '',
            'content' => "## Chennai Airport\n\nDoor-to-door transfers, day and night.",
            'is_published' => '1',
            'nav_placement' => 'none',
            'nav_order' => '',
            ...$overrides,
        ];
    }

    public function test_listing_shows_an_empty_state_then_pages(): void
    {
        $this->actingAs($this->admin())->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee(['Website Pages', 'No custom pages yet', 'Add New Page'])
            ->assertSee('href="'.route('admin.pages.create').'"', false);

        $page = $this->page(['is_published' => false]);

        $this->get(route('admin.pages.index'))
            ->assertDontSee('No custom pages yet')
            ->assertSee(['Corporate Travel', '/corporate-travel', 'Service page', 'Draft'])
            ->assertSee('href="'.route('admin.pages.edit', $page).'"', false)
            ->assertSee('href="'.route('admin.pages.preview', $page).'"', false)
            ->assertSee('action="'.route('admin.pages.destroy', $page).'"', false);
    }

    public function test_sidebar_links_to_website_pages(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.pages.index').'"', false)
            ->assertSee('Website Pages');
    }

    public function test_admin_can_create_a_published_page_with_a_generated_slug(): void
    {
        $this->actingAs($this->admin())->get(route('admin.pages.create'))->assertOk()->assertSee('Add new page');

        $response = $this->post(route('admin.pages.store'), $this->validInput(['meta_title' => 'Airport taxi', 'meta_description' => 'Reliable airport cabs.']));

        $page = Page::firstOrFail();
        $response->assertRedirect(route('admin.pages.edit', $page))->assertSessionHasNoErrors();
        $this->assertSame('airport-transfer', $page->slug);
        $this->assertTrue($page->is_published);
        $this->assertNotNull($page->published_at);

        $this->get('/airport-transfer')
            ->assertOk()
            ->assertSee(['Airport Transfer', 'Chennai Airport', 'Door-to-door transfers, day and night.'])
            ->assertSee('<h2>Chennai Airport</h2>', false)
            ->assertSee('<title>Airport taxi | ', false)
            ->assertSee('Reliable airport cabs.');
    }

    public function test_typed_slugs_are_normalised(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), $this->validInput(['slug' => '  Airport  Transfer Chennai! ']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pages', ['slug' => 'airport-transfer-chennai']);
    }

    public function test_admin_can_edit_a_page_and_keep_its_own_slug(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())->get(route('admin.pages.edit', $page))->assertOk()->assertSee('value="corporate-travel"', false);

        $this->put(route('admin.pages.update', $page), $this->validInput(['title' => 'Corporate Travel Plans', 'slug' => 'corporate-travel', 'content' => 'Updated content for companies.']))
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasNoErrors();

        $this->assertSame('Corporate Travel Plans', $page->fresh()->title);
        $this->get('/corporate-travel')->assertOk()->assertSee('Updated content for companies.');

        // Changing the slug moves the page.
        $this->put(route('admin.pages.update', $page), $this->validInput(['slug' => 'business-travel']))->assertSessionHasNoErrors();
        $this->get('/business-travel')->assertOk();
        $this->get('/corporate-travel')->assertNotFound();
    }

    public function test_slugs_must_be_unique(): void
    {
        $this->page();
        $other = $this->page(['title' => 'Other', 'slug' => 'other-page']);

        $this->actingAs($this->admin())->post(route('admin.pages.store'), $this->validInput(['slug' => 'corporate-travel']))
            ->assertSessionHasErrors(['slug' => 'Another page already uses this URL. Choose a different slug.']);

        $this->put(route('admin.pages.update', $other), $this->validInput(['slug' => 'corporate-travel']))
            ->assertSessionHasErrors('slug');

        $this->assertSame('other-page', $other->fresh()->slug);
        $this->assertSame(2, Page::count());
    }

    public function test_slugs_of_existing_routes_are_rejected(): void
    {
        $this->actingAs($this->admin());

        foreach (['booking', 'contact', 'services', 'about', 'destinations', 'admin', 'sitemap', 'sitemap.xml', 'photo-credits', 'places', 'storage', 'build', 'images', 'robots', 'Contact'] as $slug) {
            $this->post(route('admin.pages.store'), $this->validInput(['slug' => $slug]))
                ->assertSessionHasErrors('slug', "Slug {$slug} should be rejected");
        }

        // A title that slugs to a reserved path is rejected too.
        $this->post(route('admin.pages.store'), $this->validInput(['title' => 'Booking', 'slug' => '']))->assertSessionHasErrors('slug');

        $this->assertSame(0, Page::count());
    }

    public function test_built_in_pages_are_not_affected(): void
    {
        $this->page(['slug' => 'about-us', 'title' => 'Custom About Us']);

        $this->get('/about')->assertOk()->assertDontSee('Custom About Us');
        $this->get('/contact')->assertOk()->assertDontSee('Custom About Us');
        $this->get('/about-us')->assertOk()->assertSee('Custom About Us');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_draft_pages_are_not_public_but_admins_can_preview_them(): void
    {
        $page = $this->page(['is_published' => false]);

        $this->get('/corporate-travel')->assertNotFound();
        $this->get(route('admin.pages.preview', $page))->assertRedirect(route('admin.login'));

        $this->actingAs($this->admin())->get(route('admin.pages.preview', $page))
            ->assertOk()
            ->assertSee(['Preview', 'this draft is only visible to admins', 'Corporate Travel'])
            ->assertSee('noindex, nofollow', false);

        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_admin_can_publish_and_unpublish(): void
    {
        $page = $this->page(['is_published' => false]);
        $this->actingAs($this->admin());

        $this->from(route('admin.pages.index'))->patch(route('admin.pages.toggle', $page))
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHas('status', '“Corporate Travel” is now published.');
        $this->assertTrue($page->fresh()->is_published);
        $this->assertNotNull($page->fresh()->published_at);
        $this->get('/corporate-travel')->assertOk();

        $this->patch(route('admin.pages.toggle', $page))->assertSessionHas('status', '“Corporate Travel” is now a draft.');
        $this->assertFalse($page->fresh()->is_published);
        $this->assertNull($page->fresh()->published_at);
        $this->get('/corporate-travel')->assertNotFound();

        // Unpublishing from the edit form works too.
        $this->put(route('admin.pages.update', $page), $this->validInput(['slug' => 'corporate-travel', 'is_published' => '1']));
        $this->get('/corporate-travel')->assertOk();
        $this->put(route('admin.pages.update', $page), $this->validInput(['slug' => 'corporate-travel', 'is_published' => '0']));
        $this->get('/corporate-travel')->assertNotFound();
    }

    public function test_admin_can_delete_a_page(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHas('status', '“Corporate Travel” was deleted.');

        $this->assertModelMissing($page);
        $this->get('/corporate-travel')->assertNotFound();
    }

    public function test_content_is_rendered_without_html_or_unsafe_links(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), $this->validInput([
            'content' => "Hello <script>alert('xss')</script> world.\n\n<img src=x onerror=alert(1)>\n\n[Click](javascript:alert(1)) and [safe](https://h2cabs.example/info).",
            'title' => 'Safety <b>Test</b>',
            'slug' => 'safety-test',
        ]))->assertSessionHasNoErrors();

        $html = $this->get('/safety-test')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('onerror=alert', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringContainsString('href="https://h2cabs.example/info"', $html);
        $this->assertStringContainsString('Safety &lt;b&gt;Test&lt;/b&gt;', $html);
    }

    public function test_validation_rules(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), $this->validInput([
            'title' => '',
            'content' => 'short',
            'page_type' => 'evil',
            'nav_placement' => 'sidebar',
            'meta_title' => str_repeat('x', 71),
            'featured_image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['title', 'content', 'page_type', 'nav_placement', 'meta_title', 'featured_image']);

        $this->assertSame(0, Page::count());
    }

    public function test_featured_image_is_stored_and_replaced(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The GD extension is needed to create test images.');
        }

        Storage::fake('public');
        $this->actingAs($this->admin())->post(route('admin.pages.store'), $this->validInput([
            'featured_image' => UploadedFile::fake()->image('hero.jpg', 1200, 600),
        ]))->assertSessionHasNoErrors();

        $page = Page::firstOrFail();
        Storage::disk('public')->assertExists($page->featured_image_path);
        $old = $page->featured_image_path;

        $this->put(route('admin.pages.update', $page), $this->validInput([
            'slug' => $page->slug,
            'featured_image' => UploadedFile::fake()->image('new.png', 1200, 600),
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($page->fresh()->featured_image_path);
        $this->get('/'.$page->slug)->assertSee($page->fresh()->imageUrl(), false);
    }

    public function test_pages_can_be_linked_from_the_footer_or_navigation(): void
    {
        $this->page(['title' => 'Footer Policy', 'slug' => 'cancellation-policy', 'nav_placement' => 'footer']);
        $this->page(['title' => 'Header Offer', 'slug' => 'monsoon-offer', 'nav_placement' => 'header']);
        $this->page(['title' => 'Hidden Draft', 'slug' => 'hidden-draft', 'nav_placement' => 'footer', 'is_published' => false]);
        $this->page(['title' => 'Unlinked Page', 'slug' => 'unlinked-page']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.url('/cancellation-policy').'"', $html);
        $this->assertStringContainsString('href="'.url('/monsoon-offer').'"', $html);
        $this->assertStringNotContainsString('Hidden Draft', $html);
        $this->assertStringNotContainsString('Unlinked Page', $html);
        // The built-in navigation is still there.
        $this->assertStringContainsString('href="'.route('contact').'"', $html);
        $this->get('/monsoon-offer')->assertSee('aria-current="page"', false);
    }

    public function test_sitemap_lists_published_pages_only(): void
    {
        $this->page();
        $this->page(['slug' => 'secret-draft', 'is_published' => false]);

        $this->get('/sitemap.xml')
            ->assertSee(url('/corporate-travel'))
            ->assertDontSee('secret-draft');
    }

    public function test_guests_and_non_admins_cannot_manage_pages(): void
    {
        $page = $this->page();

        $this->get(route('admin.pages.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.pages.store'), $this->validInput())->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.pages.index'))->assertForbidden();
        $this->post(route('admin.pages.store'), $this->validInput())->assertForbidden();
        $this->delete(route('admin.pages.destroy', $page))->assertForbidden();
        $this->patch(route('admin.pages.toggle', $page))->assertForbidden();

        $this->assertModelExists($page);
        $this->assertSame(1, Page::count());
    }
}
