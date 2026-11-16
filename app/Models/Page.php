<?php

namespace App\Models;

use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * A custom website page created in Admin → Website Pages and served at
 * /{slug} while published. Content is Markdown; raw HTML is stripped when
 * rendering, so stored content can never inject scripts.
 *
 * @property string $title
 * @property string $slug
 * @property string $page_type
 * @property ?string $meta_title
 * @property ?string $meta_description
 * @property string $content
 * @property ?string $featured_image_path
 * @property bool $is_published
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property string $nav_placement
 * @property int $nav_order
 */
class Page extends Model
{
    public const TYPES = [
        'standard' => 'Standard page',
        'service' => 'Service page',
        'landing' => 'Landing page',
        'legal' => 'Policy / legal',
    ];

    public const PLACEMENTS = [
        'none' => 'Not linked',
        'footer' => 'Website footer',
        'header' => 'Main navigation',
    ];

    /** Slug format: lowercase words separated by single hyphens. */
    public const SLUG_PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';

    private const NAV_CACHE_KEY = 'pages_navigation';

    /**
     * Slugs that can never be used, on top of every first URL segment the
     * application already routes (see reservedSlugs()).
     */
    private const ALWAYS_RESERVED = [
        'admin', 'api', 'booking', 'contact', 'services', 'about', 'destinations', 'photo-credits',
        'places', 'sitemap', 'sitemap-xml', 'storage', 'build', 'images', 'login', 'logout', 'register',
        'up', 'robots', 'favicon', 'index', 'home', 'vendor', 'livewire', 'sanctum',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title', 'slug', 'page_type', 'meta_title', 'meta_description', 'content',
        'is_published', 'nav_placement', 'nav_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'nav_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Keep the first publish date; clear it when unpublished.
        static::saving(function (Page $page) {
            if ($page->is_published) {
                $page->published_at ??= now();
            } else {
                $page->published_at = null;
            }
        });

        $forget = fn () => Cache::forget(self::NAV_CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    /** @param  Builder<Page>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function url(): string
    {
        return route('pages.show', $this->slug);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->page_type] ?? self::TYPES['standard'];
    }

    public function imageUrl(): ?string
    {
        return $this->featured_image_path ? Storage::disk(ImageStore::DISK)->url($this->featured_image_path) : null;
    }

    /**
     * The content as safe HTML: Markdown is converted, any raw HTML the
     * author typed is removed, and javascript:/data: links are dropped.
     */
    public function renderedContent(): HtmlString
    {
        return new HtmlString(Str::markdown($this->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]));
    }

    /** Plain-text summary for the meta description when none is set. */
    public function excerpt(int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($this->renderedContent()->toHtml())));

        return Str::limit($text, $limit, '…');
    }

    /**
     * Published pages linked in the footer or main navigation, cached until
     * a page changes. Empty before the table is migrated.
     *
     * @return Collection<int, array{label: string, url: string, path: string}>
     */
    public static function navigation(string $placement): Collection
    {
        try {
            $all = Cache::rememberForever(self::NAV_CACHE_KEY, fn () => self::query()->published()
                ->where('nav_placement', '!=', 'none')
                ->orderBy('nav_order')->orderBy('title')
                ->get(['title', 'slug', 'nav_placement'])
                ->map(fn (Page $page) => ['placement' => $page->nav_placement, 'label' => $page->title, 'slug' => $page->slug])
                ->all());
        } catch (QueryException) {
            return collect();
        }

        return collect($all)->where('placement', $placement)
            ->map(fn (array $item) => ['label' => $item['label'], 'url' => route('pages.show', $item['slug']), 'path' => $item['slug']])
            ->values();
    }

    /**
     * Slugs a custom page may not use: the first segment of every existing
     * route (so /booking, /admin, /sitemap.xml ... always win), top-level
     * files and folders in public/, and a fixed list of common paths.
     *
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        $fromRoutes = collect(Route::getRoutes()->getRoutes())
            ->reject(fn ($route) => $route->getName() === 'pages.show')
            ->map(fn ($route) => strtolower(explode('/', trim($route->uri(), '/'))[0]))
            ->filter(fn (string $segment) => $segment !== '' && ! str_starts_with($segment, '{'));

        $fromPublic = collect(@scandir(public_path()) ?: [])
            ->reject(fn (string $name) => str_starts_with($name, '.'))
            ->map(fn (string $name) => strtolower(pathinfo($name, PATHINFO_FILENAME)));

        return $fromRoutes->merge($fromPublic)->merge(self::ALWAYS_RESERVED)
            // "sitemap.xml" also blocks "sitemap" and "sitemap-xml".
            ->flatMap(fn (string $slug) => [$slug, Str::before($slug, '.'), Str::slug($slug)])
            ->filter()->unique()->values()->all();
    }
}
