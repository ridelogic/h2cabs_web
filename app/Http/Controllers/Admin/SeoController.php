<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use App\Support\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Per-page SEO overrides for the public pages listed in
 * config('site.seo.pages'). Clearing a field restores the default.
 */
class SeoController extends Controller
{
    public function index(): View
    {
        return view('admin.seo.index', [
            'pages' => collect(config('site.seo.pages'))->map(fn (array $page, string $route) => [
                'route' => $route,
                'label' => $page['label'],
                'url' => route($route),
                'seo' => SeoMeta::page($route),
                'customised' => SeoPage::forRoute($route) !== null,
            ]),
        ]);
    }

    public function edit(string $page): View
    {
        $this->ensurePage($page);
        $defaults = config('site.seo.pages')[$page];

        return view('admin.seo.edit', [
            'route' => $page,
            'label' => $defaults['label'],
            'url' => route($page),
            'record' => SeoPage::firstWhere('route_name', $page),
            'defaultTitle' => filled($defaults['title']) ? $defaults['title'].' | '.config('site.seo.title_suffix') : config('site.seo.default_title'),
            'defaultDescription' => $defaults['description'] ?? config('site.seo.default_description'),
            'seo' => SeoMeta::page($page),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $this->ensurePage($page);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:70'],
            'description' => ['nullable', 'string', 'max:300'],
            'indexable' => ['boolean'],
        ], [
            'title.max' => 'Keep the title under 70 characters so search results do not cut it off.',
        ]);

        $data = [
            'title' => filled($data['title'] ?? null) ? trim($data['title']) : null,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'indexable' => $request->boolean('indexable'),
        ];

        // Nothing customised: remove the row so the defaults apply.
        if ($data['title'] === null && $data['description'] === null && $data['indexable']) {
            SeoPage::where('route_name', $page)->get()->each->delete();
        } else {
            SeoPage::updateOrCreate(['route_name' => $page], $data);
        }

        return redirect()->route('admin.seo.edit', $page)->with('status', 'SEO saved for '.config('site.seo.pages')[$page]['label'].'.');
    }

    private function ensurePage(string $page): void
    {
        abort_unless(array_key_exists($page, config('site.seo.pages')), 404);
    }
}
