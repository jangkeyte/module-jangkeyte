<?php

namespace JangKeyte\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Store a public asset (e.g. post/banner/avatar image) with a safe random filename.
     * Never trusts client original filename.
     */
    public function uploadPublic(UploadedFile $file, string $folder, ?string $oldFilename = null): string
    {
        $prefix = trim(config('jangkeyte.uploads.public_prefix', 'storage/uploads'), '/');
        $targetDir = public_path("{$prefix}/" . trim($folder, '/'));

        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $filename = Str::slug($folder, '_') . '_' . Str::lower(Str::random(16)) . '.' . $ext;

        $file->move($targetDir, $filename);

        if ($oldFilename && ! in_array($oldFilename, ['default.png', 'default.jpg', 'default_photo.jpg'], true)) {
            $this->deletePublic($folder, $oldFilename);
        }

        return $filename;
    }

    public function deletePublic(string $folder, ?string $filename): void
    {
        if (! $filename || in_array($filename, ['default.png', 'default.jpg', 'default_photo.jpg'], true)) {
            return;
        }

        $prefix = trim(config('jangkeyte.uploads.public_prefix', 'storage/uploads'), '/');
        $safeName = basename($filename);
        $path = public_path("{$prefix}/" . trim($folder, '/') . "/{$safeName}");

        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    /**
     * Store sensitive files (e.g. CCCD / ID cards / verification docs) on private storage disk.
     */
    public function uploadPrivate(UploadedFile $file, string $folder, ?string $oldPath = null): string
    {
        $disk = config('jangkeyte.uploads.private_disk', 'local');
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $filename = Str::slug($folder, '_') . '_' . Str::lower(Str::random(20)) . '.' . $ext;
        $relativePath = 'private/' . trim($folder, '/') . '/' . $filename;

        Storage::disk($disk)->putFileAs('private/' . trim($folder, '/'), $file, $filename);

        if ($oldPath && Storage::disk($disk)->exists($oldPath)) {
            Storage::disk($disk)->delete($oldPath);
        }

        return $relativePath;
    }

    /**
     * Store a file inside the shared Media Library subfolder without deleting previous assets.
     * Returns the relative path under the media root (e.g. "trips/trips_abc123.jpg").
     */
    public function storeMediaUpload(UploadedFile $file, string $subfolder): string
    {
        $normalized = str_replace('\\', '/', trim($subfolder));
        if (str_contains($normalized, "\0") || str_contains($normalized, '..')) {
            throw new \InvalidArgumentException('Invalid media subfolder.');
        }

        $segments = array_values(array_filter(
            array_map(static fn (string $seg): string => Str::slug($seg, '_'), explode('/', trim($normalized, '/'))),
            static fn (string $seg): bool => $seg !== ''
        ));

        if ($segments === []) {
            throw new \InvalidArgumentException('Media subfolder must not be empty.');
        }

        $cleanFolder = implode('/', $segments);
        $ext = app(MediaService::class)->validateUploadedFile($file);

        $prefix = trim(str_replace('\\', '/', (string) config('jangkeyte.media.root_dir', config('jangkeyte.uploads.public_prefix', 'storage/uploads'))), '/');
        $targetDir = public_path("{$prefix}/{$cleanFolder}");

        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $slugPrefix = Str::slug(str_replace('/', '_', $cleanFolder), '_') ?: 'media';
        $filename = $slugPrefix . '_' . Str::lower(Str::random(16)) . '.' . $ext;

        $file->move($targetDir, $filename);

        return "{$cleanFolder}/{$filename}";
    }

    /**
     * Resolve a public URL from stored media path, legacy bare filename, or external URL.
     */
    public function resolveMediaUrl(?string $storedValue, ?string $defaultSubfolder = null): ?string
    {
        if ($storedValue === null) {
            return null;
        }

        $value = trim(str_replace('\\', '/', $storedValue));
        if ($value === '' || str_contains($value, "\0")) {
            return null;
        }

        $variants = [$value];
        $decoded = $value;

        for ($i = 0; $i < 3; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $variants[] = $next;
            $decoded = $next;
        }

        foreach ($variants as $variant) {
            if (str_contains($variant, "\0")) {
                return null;
            }
        }

        if (preg_match('#^(?:https?:)?//#i', $value) === 1) {
            return $value;
        }

        foreach ($variants as $variant) {
            $normVariant = str_replace('\\', '/', $variant);
            foreach (explode('/', trim($normVariant, '/')) as $segment) {
                if ($segment === '..') {
                    return null;
                }
            }
        }

        $prefix = trim(str_replace('\\', '/', (string) config('jangkeyte.media.root_dir', config('jangkeyte.uploads.public_prefix', 'storage/uploads'))), '/');
        $hasLeadingSlash = str_starts_with($value, '/');
        $trimmed = ltrim($value, '/');

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, $prefix . '/')) {
            return asset($trimmed);
        }

        if ($hasLeadingSlash && (str_starts_with($trimmed, 'assets/') || File::isFile(public_path($trimmed)))) {
            return asset($trimmed);
        }

        if (! str_contains($trimmed, '/') && $defaultSubfolder !== null) {
            $folder = trim(str_replace('\\', '/', $defaultSubfolder), '/');
            if ($folder !== '') {
                return asset("{$prefix}/{$folder}/{$trimmed}");
            }
        }

        return asset("{$prefix}/{$trimmed}");
    }
}
