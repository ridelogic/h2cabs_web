<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A member of the Helping Hands Driver's Association. Managed in the admin
 * dashboard; only active members marked "show on website" appear publicly.
 * Deleting soft-deletes the row so imported records are not re-created by
 * a later seed.
 */
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string, string> */
    public const GROUPS = [
        'office_bearer' => 'Office bearers',
        'executive' => 'Executive members',
        'member' => 'Members',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'role',
        'member_group',
        'photo_path',
        'phone',
        'bio',
        'member_since',
        'is_active',
        'is_published',
        'sort_order',
        'source_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'member_since' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** Members visible on the public website, in display order. */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true)->where('is_published', true)->ordered();
    }

    /** @param  Builder<Member>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function isPublic(): bool
    {
        return $this->is_active && $this->is_published;
    }

    public function groupLabel(): string
    {
        return self::GROUPS[$this->member_group] ?? self::GROUPS['member'];
    }

    public function initials(): string
    {
        // "G. Prabaharan" → "GP"; skips punctuation-only parts.
        return Str::of($this->name)->explode(' ')
            ->map(fn (string $part) => preg_replace('/[^\pL]/u', '', $part))
            ->filter()->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    public function telHref(): ?string
    {
        return $this->phone ? 'tel:'.preg_replace('/[^\d+]/', '', $this->phone) : null;
    }
}
