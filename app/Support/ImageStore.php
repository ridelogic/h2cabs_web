<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Page;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores admin-uploaded images on the "public" disk (storage/app/public,
 * served via the `public/storage` link) under random file names, and
 * deletes them only when no member, vehicle or page still references the file.
 */
class ImageStore
{
    public const DISK = 'public';

    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    public function deleteIfUnreferenced(?string $path): void
    {
        if (! $path) {
            return;
        }

        $inUse = Member::withTrashed()->where('photo_path', $path)->exists()
            || Vehicle::query()->where('image_path', $path)->exists()
            || Page::query()->where('featured_image_path', $path)->exists();

        if (! $inUse) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
