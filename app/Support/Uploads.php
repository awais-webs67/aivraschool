<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Photos are stored privately (storage/app/private) and served through the app,
 * so no "storage:link" symlink is needed on shared hosting.
 */
class Uploads
{
    /** Validation rules for a photo: JPG, PNG or WebP up to 2 MB. */
    public const PHOTO_RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    public static function store(?UploadedFile $file, string $folder): ?string
    {
        return $file ? $file->storeAs($folder, Str::random(32).'.'.$file->extension()) : null;
    }

    /** Replace an old file with a new upload (keeps the old one when nothing was uploaded). */
    public static function replace(?UploadedFile $file, ?string $old, string $folder): ?string
    {
        if (! $file) {
            return $old;
        }

        if ($old) {
            Storage::delete($old);
        }

        return self::store($file, $folder);
    }
}
