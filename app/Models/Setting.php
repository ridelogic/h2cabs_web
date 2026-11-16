<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * A website setting edited in the admin dashboard. Keys are config paths
 * under `site.` (see App\Support\SiteSettings). A non-empty value overrides
 * the .env default at runtime; an empty or missing value falls back to it.
 */
class Setting extends Model
{
    private const CACHE_KEY = 'site_settings';

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'value'];

    /**
     * All saved settings as [key => value], cached until the next change.
     * Returns [] if the table does not exist yet (before migrating).
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->whereNotNull('value')->pluck('value', 'key')->all());
        } catch (QueryException) {
            return [];
        }
    }

    public static function put(string $key, ?string $value): void
    {
        $value = $value === null || trim($value) === '' ? null : trim($value);

        if ($value === null) {
            self::query()->where('key', $key)->delete();
        } else {
            self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
