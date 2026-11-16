<?php

namespace Tests\Feature\Admin;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function validMember(array $overrides = []): array
    {
        return array_merge([
            'name' => 'R. Selvam',
            'role' => 'Executive Member',
            'member_group' => 'executive',
            'is_active' => '1',
            'is_published' => '1',
        ], $overrides);
    }

    public function test_non_admins_cannot_create_update_or_delete_members(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create();

        $this->post(route('admin.members.store'), $this->validMember())->assertRedirect(route('admin.login'));

        $this->actingAs($user);
        $this->post(route('admin.members.store'), $this->validMember())->assertForbidden();
        $this->put(route('admin.members.update', $member), $this->validMember())->assertForbidden();
        $this->patch(route('admin.members.toggle', $member), ['field' => 'is_published'])->assertForbidden();
        $this->delete(route('admin.members.destroy', $member))->assertForbidden();

        $this->assertSame(1, Member::count());
        $this->assertNotSame('R. Selvam', $member->fresh()->name);
    }

    public function test_admin_can_add_a_member_with_a_photo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.members.store'), $this->validMember(['photo' => UploadedFile::fake()->image('selvam.jpg', 300, 300)]))
            ->assertRedirect();

        $member = Member::where('name', 'R. Selvam')->first();
        $this->assertNotNull($member->photo_path);
        Storage::disk('public')->assertExists($member->photo_path);
        $this->assertStringStartsWith('members/', $member->photo_path);
    }

    public function test_uploads_are_validated(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.members.store'), $this->validMember(['photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors('photo');
        $this->post(route('admin.members.store'), $this->validMember(['photo' => UploadedFile::fake()->image('tiny.jpg', 40, 40)]))
            ->assertSessionHasErrors('photo');
        $this->post(route('admin.members.store'), $this->validMember(['photo' => UploadedFile::fake()->image('huge.jpg', 500, 500)->size(3000)]))
            ->assertSessionHasErrors('photo');
        $this->post(route('admin.members.store'), $this->validMember(['name' => '', 'phone' => 'call me']))
            ->assertSessionHasErrors(['name', 'phone']);

        $this->assertSame(0, Member::count());
    }

    public function test_admin_can_edit_and_replace_photo_and_old_file_is_removed(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.members.store'), $this->validMember(['photo' => UploadedFile::fake()->image('a.jpg', 300, 300)]));
        $member = Member::first();
        $oldPhoto = $member->photo_path;

        $this->put(route('admin.members.update', $member), $this->validMember([
            'name' => 'R. Selvam Kumar',
            'phone' => '+91 98765 43210',
            'photo' => UploadedFile::fake()->image('b.png', 400, 400),
        ]))->assertRedirect(route('admin.members.edit', $member));

        $member->refresh();
        $this->assertSame('R. Selvam Kumar', $member->name);
        $this->assertSame('+91 98765 43210', $member->phone);
        $this->assertNotSame($oldPhoto, $member->photo_path);
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertExists($member->photo_path);
    }

    public function test_shared_photo_is_not_deleted_while_still_referenced(): void
    {
        Storage::disk('public')->put('members/shared.jpg', 'image');
        $a = Member::factory()->create(['photo_path' => 'members/shared.jpg']);
        Member::factory()->create(['photo_path' => 'members/shared.jpg']);

        $this->actingAs($this->admin())->delete(route('admin.members.destroy', $a));

        Storage::disk('public')->assertExists('members/shared.jpg');
    }

    public function test_hiding_or_deactivating_keeps_the_record_but_removes_it_from_the_website(): void
    {
        $member = Member::factory()->create(['name' => 'Visible Person']);
        $this->actingAs($this->admin());

        $this->get(route('about'))->assertSee('Visible Person');

        $this->patch(route('admin.members.toggle', $member), ['field' => 'is_published'])->assertRedirect();
        $this->assertFalse($member->fresh()->is_published);
        $this->get(route('about'))->assertDontSee('Visible Person');

        $this->patch(route('admin.members.toggle', $member), ['field' => 'is_published']);
        $this->patch(route('admin.members.toggle', $member), ['field' => 'is_active']);
        $this->assertFalse($member->fresh()->is_active);
        $this->get(route('about'))->assertDontSee('Visible Person');

        $this->assertSame(1, Member::count());
        $this->patch(route('admin.members.toggle', $member), ['field' => 'name'])->assertSessionHasErrors('field');
    }

    public function test_delete_soft_deletes_and_removes_the_photo(): void
    {
        Storage::disk('public')->put('members/own.jpg', 'image');
        $member = Member::factory()->create(['name' => 'Gone Person', 'photo_path' => 'members/own.jpg']);

        $this->actingAs($this->admin())->delete(route('admin.members.destroy', $member))
            ->assertRedirect(route('admin.members.index'));

        $this->assertSoftDeleted($member);
        Storage::disk('public')->assertMissing('members/own.jpg');
        $this->get(route('admin.members.index'))->assertSee('Gone Person was deleted.'); // flash message
        $this->get(route('admin.members.index'))->assertDontSee('Gone Person');
        $this->get(route('about'))->assertOk()->assertDontSee('Gone Person');
    }

    public function test_members_can_be_reordered(): void
    {
        $first = Member::factory()->create(['name' => 'First', 'sort_order' => 10]);
        $second = Member::factory()->create(['name' => 'Second', 'sort_order' => 20]);
        Member::factory()->create(['name' => 'Third', 'sort_order' => 30]);

        $this->actingAs($this->admin())->patch(route('admin.members.move', $second), ['direction' => 'up']);

        $this->assertSame(['Second', 'First', 'Third'], Member::query()->ordered()->pluck('name')->all());

        $this->patch(route('admin.members.move', $first), ['direction' => 'down']);
        $this->assertSame(['Second', 'Third', 'First'], Member::query()->ordered()->pluck('name')->all());
    }

    public function test_edit_page_previews_the_public_card(): void
    {
        $member = Member::factory()->create(['name' => 'Preview Person', 'role' => 'Treasurer', 'is_published' => false]);

        $this->actingAs($this->admin())->get(route('admin.members.edit', $member))
            ->assertOk()
            ->assertSee('Public card preview')
            ->assertSee('Treasurer')
            ->assertSee('Not visible publicly');
    }

    public function test_member_input_is_escaped(): void
    {
        $member = Member::factory()->create(['name' => '<script>alert(1)</script>', 'bio' => '<b>bold</b>']);

        $this->get(route('about'))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        $this->actingAs($this->admin())->get(route('admin.members.edit', $member))->assertDontSee('<script>alert(1)</script>', false);
    }
}
