<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Support\ImageStore;
use App\Support\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Custom website pages (Admin → Website Pages). Published pages are served
 * publicly by App\Http\Controllers\CustomPageController at /{slug}.
 */
class PageController extends Controller
{
    public function __construct(private readonly ImageStore $images) {}

    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()->latest('updated_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', [
            'page' => new Page(['page_type' => 'standard', 'nav_placement' => 'none', 'is_published' => false]),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = new Page($this->attributes($request));

        if ($request->hasFile('featured_image')) {
            $page->featured_image_path = $this->images->store($request->file('featured_image'), 'pages');
        }

        $page->save();

        return redirect()->route('admin.pages.edit', $page)
            ->with('status', "“{$page->title}” was created".($page->is_published ? ' and published.' : ' as a draft.'));
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->fill($this->attributes($request));
        $oldImage = $page->featured_image_path;

        if ($request->hasFile('featured_image')) {
            $page->featured_image_path = $this->images->store($request->file('featured_image'), 'pages');
        } elseif ($request->boolean('remove_image')) {
            $page->featured_image_path = null;
        }

        $page->save();

        if ($oldImage !== $page->featured_image_path) {
            $this->images->deleteIfUnreferenced($oldImage);
        }

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Changes saved.');
    }

    /** Publish or unpublish without opening the form. */
    public function toggle(Page $page): RedirectResponse
    {
        $page->update(['is_published' => ! $page->is_published]);

        return back()->with('status', "“{$page->title}” is now ".($page->is_published ? 'published.' : 'a draft.'));
    }

    /** Admin-only preview, so drafts can be checked before publishing. */
    public function preview(Page $page): View
    {
        return view('pages.custom', [
            'page' => $page,
            'preview' => true,
            'seo' => SeoMeta::make([
                'title' => $page->meta_title ?: $page->title,
                'description' => $page->meta_description ?: $page->excerpt(),
                'indexable' => false,
            ]),
        ]);
    }

    public function destroy(Page $page): RedirectResponse
    {
        $image = $page->featured_image_path;
        $page->delete();
        $this->images->deleteIfUnreferenced($image);

        return redirect()->route('admin.pages.index')->with('status', "“{$page->title}” was deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(PageRequest $request): array
    {
        $attributes = $request->safe()->except(['featured_image', 'remove_image']);
        $attributes['nav_order'] = (int) ($attributes['nav_order'] ?? 0);
        $attributes['meta_title'] = filled($attributes['meta_title'] ?? null) ? trim($attributes['meta_title']) : null;
        $attributes['meta_description'] = filled($attributes['meta_description'] ?? null) ? trim($attributes['meta_description']) : null;

        return $attributes;
    }
}
