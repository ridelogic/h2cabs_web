<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SeoPage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect(config('site.sitemap_routes'))
            // Pages hidden from search engines in Admin → SEO are left out.
            ->reject(fn (array $options, string $name) => (SeoPage::forRoute($name)['indexable'] ?? true) === false)
            ->map(fn (array $options, string $name) => [
                'loc' => route($name),
                'changefreq' => $options['changefreq'] ?? 'monthly',
                'priority' => $options['priority'] ?? '0.5',
            ])
            ->values();

        // Published custom pages (Admin → Website Pages).
        try {
            $urls = $urls->merge(Page::query()->published()->orderBy('slug')->get(['slug'])->map(fn (Page $page) => [
                'loc' => $page->url(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ]));
        } catch (QueryException) {
            // Pages table not migrated yet.
        }

        // The XML declaration is prepended here rather than written in Blade,
        // where "<?xml" can be misread as a PHP open tag (short_open_tag).
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
