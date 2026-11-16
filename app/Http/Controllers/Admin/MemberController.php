<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberRequest;
use App\Models\Member;
use App\Support\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function __construct(private readonly ImageStore $images) {}

    public function index(): View
    {
        return view('admin.members.index', [
            'members' => Member::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.members.form', [
            'member' => new Member(['is_active' => true, 'is_published' => true, 'member_group' => 'member']),
        ]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = new Member($this->attributes($request));
        $member->sort_order ??= (int) Member::max('sort_order') + 10;

        if ($request->hasFile('photo')) {
            $member->photo_path = $this->images->store($request->file('photo'), 'members');
        }

        $member->save();

        return redirect()->route('admin.members.edit', $member)->with('status', "{$member->name} was added.");
    }

    public function edit(Member $member): View
    {
        return view('admin.members.form', ['member' => $member]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        $member->fill($this->attributes($request));
        $oldPhoto = $member->photo_path;

        if ($request->hasFile('photo')) {
            $member->photo_path = $this->images->store($request->file('photo'), 'members');
        } elseif ($request->boolean('remove_photo')) {
            $member->photo_path = null;
        }

        $member->save();

        if ($oldPhoto !== $member->photo_path) {
            $this->images->deleteIfUnreferenced($oldPhoto);
        }

        return redirect()->route('admin.members.edit', $member)->with('status', 'Changes saved.');
    }

    /** Toggle "active" or "show on website" without opening the form. */
    public function toggle(Request $request, Member $member): RedirectResponse
    {
        $field = $request->validate(['field' => ['required', 'in:is_active,is_published']])['field'];
        $member->update([$field => ! $member->{$field}]);

        return back()->with('status', "{$member->name} updated.");
    }

    public function move(Request $request, Member $member): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($member, $direction) {
            // Normalise to 10, 20, 30… then swap with the neighbour.
            $ordered = Member::query()->ordered()->lockForUpdate()->get()->values();
            $ordered->each(fn (Member $m, int $i) => $m->sort_order = ($i + 1) * 10);

            $index = $ordered->search(fn (Member $m) => $m->is($member));
            $swap = $direction === 'up' ? $index - 1 : $index + 1;

            if ($swap >= 0 && $swap < $ordered->count()) {
                [$ordered[$index]->sort_order, $ordered[$swap]->sort_order] = [$ordered[$swap]->sort_order, $ordered[$index]->sort_order];
            }

            // (A plain loop: Collection::each() would stop at the first unchanged member.)
            foreach ($ordered as $m) {
                if ($m->isDirty('sort_order')) {
                    $m->save();
                }
            }
        });

        return back();
    }

    /**
     * Soft-deletes the member (so a later seed does not re-create an imported
     * record) and removes the photo if nothing else uses it.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $photo = $member->photo_path;

        $member->photo_path = null;
        $member->save();
        $member->delete();

        $this->images->deleteIfUnreferenced($photo);

        return redirect()->route('admin.members.index')->with('status', "{$member->name} was deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(MemberRequest $request): array
    {
        $attributes = $request->safe()->except(['photo', 'remove_photo']);

        // A blank display order keeps the current position.
        if (($attributes['sort_order'] ?? null) === null) {
            unset($attributes['sort_order']);
        }

        return $attributes;
    }
}
