<?php

namespace App\Modules\Products\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    public const MAX_FILES = 10;
    public const MAX_FILE_SIZE_KB = 5120;
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function uploadMultiple(array $files, string $disk = 'public', string $folder = 'products'): array
    {
        if (count($files) > self::MAX_FILES) {
            throw new \DomainException("Maximum " . self::MAX_FILES . " images allowed.");
        }

        $uploaded = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
                throw new \DomainException("Invalid file type: {$file->getClientOriginalName()}");
            }

            if ($file->getSize() > self::MAX_FILE_SIZE_KB * 1024) {
                throw new \DomainException("File too large: {$file->getClientOriginalName()}");
            }

            $path = $file->store($folder, $disk);
            $uploaded[] = $path;
        }

        return $uploaded;
    }

    public function delete(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    public function deleteMultiple(array $paths, string $disk = 'public'): int
    {
        $deleted = 0;

        foreach ($paths as $path) {
            if ($this->delete($path, $disk)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
