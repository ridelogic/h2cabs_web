<?php

namespace App\Support;

use App\Models\SeoPage;

/**
 * Per-page SEO metadata.
 *
 * Controllers build one of these and pass it to the layout as `$seo`.
 * Defaults come from config/site.php; a future CMS can construct it from
 * database fields via make() with the stored values.
 */
final class SeoMeta
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $canonical,
        public readonly string $image,
        public readonly string $type = 'website',
        public readonly bool $indexable = true,
    ) {}

    /**
     * @param  array{title?: ?string, description?: ?string, canonical?: ?string, image?: ?string, type?: string, indexable?: bool}  $overrides
     */
    public static function make(array $overrides = []): self
    {
        $config = config('site.seo');

        $title = filled($overrides['title'] ?? null)
            ? $overrides['title'].' | '.$config['title_suffix']
            : $config['default_title'];

        return new self(
            title: $title,
            description: $overrides['description'] ?? $config['default_description'],
            canonical: $overrides['canonical'] ?? url()->current(),
            image: asset($overrides['image'] ?? $config['default_image']),
            type: $overrides['type'] ?? 'website',
            indexable: ($overrides['indexable'] ?? true) && $config['indexable'],
        );
    }

    /**
     * SEO for a public page: defaults from config('site.seo.pages'), then
     * any override saved in Admin → SEO (title used verbatim, description,
     * and "hide from search engines").
     */
    public static function page(string $route): self
    {
        // Look up by key: route names such as "booking.create" contain dots.
        $defaults = config('site.seo.pages')[$route] ?? [];
        $override = SeoPage::forRoute($route);

        $seo = self::make([
            'title' => $defaults['title'] ?? null,
            'description' => filled($override['description'] ?? null) ? $override['description'] : ($defaults['description'] ?? null),
            'canonical' => route($route),
            'indexable' => $override['indexable'] ?? true,
        ]);

        if (filled($override['title'] ?? null)) {
            return new self($override['title'], $seo->description, $seo->canonical, $seo->image, $seo->type, $seo->indexable);
        }

        return $seo;
    }

    public function robots(): string
    {
        return $this->indexable ? 'index, follow' : 'noindex, nofollow';
    }
}
