<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Admin overrides for a public page's <title>, meta description and search
 * indexing. Pages without a row use the defaults written in the controllers.
 */
class SeoPage extends Model
{
    private const CACHE_KEY = 'seo_pages';

    /**
     * @var list<string>
     */
    protected $fillable = ['route_name', 'title', 'description', 'indexable'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['indexable' => 'boolean'];
    }

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    /** Overrides keyed by route name; [] before the table is migrated. */
    public static function overrides(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->get()
                ->mapWithKeys(fn (SeoPage $page) => [$page->route_name => $page->only(['title', 'description', 'indexable'])])
                ->all());
        } catch (QueryException) {
            return [];
        }
    }

    /** @return array{title: ?string, description: ?string, indexable: bool}|null */
    public static function forRoute(?string $route): ?array
    {
        return $route ? (self::overrides()[$route] ?? null) : null;
    }
}
