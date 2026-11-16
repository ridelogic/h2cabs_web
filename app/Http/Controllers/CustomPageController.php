<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\SeoMeta;
use Illuminate\Contracts\View\View;

/**
 * Public view of a published custom page at /{slug}. The route is
 * registered last, so every built-in page keeps priority; drafts and
 * unknown slugs are a 404.
 */
class CustomPageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return view('pages.custom', [
            'page' => $page,
            'preview' => false,
            'seo' => SeoMeta::make([
                'title' => $page->meta_title ?: $page->title,
                'description' => $page->meta_description ?: $page->excerpt(),
                'canonical' => $page->url(),
                'image' => $page->featured_image_path ? $page->imageUrl() : null,
                'type' => 'article',
            ]),
        ]);
    }
}
