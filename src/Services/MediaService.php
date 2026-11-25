<?php

namespace JangKeyte\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MediaService
{
    private const BLOCKED_GUESSED_EXTENSIONS = [
        'php',
        'phtml',
        'phar',
        'exe',
        'sh',
        'bat',
        'cmd',
        'com',
        'pl',
        'py',
        'cgi',
        'html',
        'htm',
        'xhtml',
        'js',
        'mjs',
        'hta',
        'vbs',
        'ps1',
    ];

    private const BLOCKED_MIME_TYPES = [
        'text/x-php',
        'application/x-httpd-php',
        'application/x-php',
        'text/php',
        'application/php',
        'text/x-shellscript',
        'application/x-sh',
        'application/x-shellscript',
        'text/html',
        'application/xhtml+xml',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
        'application/x-dosexec',
        'application/x-msdownload',
        'application/x-executable',
        'application/x-elf',
        'application/x-sharedlib',
    ];

    private const RASTER_IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'bmp',
        'ico',
    ];

    private const SVG_ALLOWED_MIME_TYPES = [
        'image/svg+xml',
        'text/xml',
        'application/xml',
        'text/plain',
    ];

    private const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'bmp',
        'ico',
    ];

    /**
     * Resolve and strictly confine a relative media path inside the configured media root directory.
     *
     * @throws InvalidArgumentException
     */
    public function resolveSafePath(string $relativePath = ''): string
    {
        $variants = [$relativePath];
        $decoded = $relativePath;

        for ($i = 0; $i < 3; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $variants[] = $next;
            $decoded = $next;
        }

        foreach ($variants as $variant) {
            if (str_contains($variant, "\0") || preg_match('/[\x00-\x1F\x7F]/', $variant) === 1) {
                throw new InvalidArgumentException('Invalid media path: null byte or control character detected.');
            }

            if (str_starts_with($variant, '\\\\') || str_starts_with($variant, '//')) {
                throw new InvalidArgumentException('Invalid media path: UNC path is not allowed.');
            }

            $normVariant = str_replace('\\', '/', $variant);
            if (preg_match('/^[A-Za-z]:/', $normVariant) === 1) {
                throw new InvalidArgumentException('Invalid media path: drive prefix is not allowed.');
            }

            $trimmedVariant = trim((string) preg_replace('#/+#', '/', $normVariant), '/');
            if ($trimmedVariant !== '') {
                foreach (explode('/', $trimmedVariant) as $segment) {
                    if ($segment === '..' || $segment === '.' || $segment === '') {
                        throw new InvalidArgumentException('Invalid media path: path traversal segment is not allowed.');
                    }
                }
            }
        }

        $rootReal = $this->rootPath();
        $cleanRel = $this->normalizeRelativePath($relativePath);

        if ($cleanRel === '') {
            return $rootReal;
        }

        $candidate = $rootReal . '/' . $cleanRel;

        if (is_link($candidate) && ! file_exists($candidate)) {
            throw new InvalidArgumentException('Invalid media path: dangling symlink.');
        }

        if (file_exists($candidate)) {
            $real = realpath($candidate);
            if ($real === false) {
                throw new InvalidArgumentException('Invalid media path: unable to resolve path.');
            }

            $realNorm = rtrim(str_replace('\\', '/', $real), '/');
            if ($realNorm !== $rootReal && ! str_starts_with($realNorm, $rootReal . '/')) {
                throw new InvalidArgumentException('Invalid media path: target escapes media root.');
            }

            return $realNorm;
        }

        $tailSegments = [];
        $probe = $candidate;

        while (! file_exists($probe) && ! is_link($probe)) {
            $parent = rtrim(str_replace('\\', '/', dirname($probe)), '/');
            if ($parent === $probe || $parent === '') {
                throw new InvalidArgumentException('Invalid media path: no existing ancestor within root.');
            }

            array_unshift($tailSegments, basename($probe));
            $probe = $parent;
        }

        if (is_link($probe) && ! file_exists($probe)) {
            throw new InvalidArgumentException('Invalid media path: dangling symlink ancestor.');
        }

        $ancestorReal = realpath($probe);
        if ($ancestorReal === false) {
            throw new InvalidArgumentException('Invalid media path: unresolvable ancestor.');
        }

        $ancestorNorm = rtrim(str_replace('\\', '/', $ancestorReal), '/');
        if ($ancestorNorm !== $rootReal && ! str_starts_with($ancestorNorm, $rootReal . '/')) {
            throw new InvalidArgumentException('Invalid media path: ancestor escapes media root.');
        }

        $resolved = $tailSegments === []
            ? $ancestorNorm
            : $ancestorNorm . '/' . implode('/', $tailSegments);

        if ($resolved !== $rootReal && ! str_starts_with($resolved, $rootReal . '/')) {
            throw new InvalidArgumentException('Invalid media path: target escapes media root.');
        }

        return $resolved;
    }

    /**
     * List directories and files inside a media directory.
     *
     * @return array{
     *     current_path: string,
     *     breadcrumbs: array<int, array{name: string, path: string}>,
     *     directories: array<int, array{name: string, relative_path: string, modified: int}>,
     *     files: array<int, array{name: string, relative_path: string, url: string, size: int, mime: string, is_image: bool, modified: int}>
     * }
     */
    public function listDirectory(string $relativePath = ''): array
    {
        $dirAbs = $this->resolveSafePath($relativePath);
        $currentPath = $this->normalizeRelativePath($relativePath);

        if (! File::isDirectory($dirAbs)) {
            throw new InvalidArgumentException("Media directory does not exist: {$currentPath}");
        }

        $rootReal = $this->rootPath();
        $breadcrumbs = [['name' => 'Root', 'path' => '']];

        if ($currentPath !== '') {
            $acc = '';
            foreach (explode('/', $currentPath) as $segment) {
                $acc = $acc === '' ? $segment : "{$acc}/{$segment}";
                $breadcrumbs[] = ['name' => $segment, 'path' => $acc];
            }
        }

        $directories = [];
        $files = [];

        $iterator = new \FilesystemIterator($dirAbs, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $item) {
            $name = $item->getFilename();
            if (str_starts_with($name, '.')) {
                continue;
            }

            $realItem = realpath($item->getPathname());
            if ($realItem === false) {
                continue;
            }

            $realNorm = rtrim(str_replace('\\', '/', $realItem), '/');
            if ($realNorm !== $rootReal && ! str_starts_with($realNorm, $rootReal . '/')) {
                continue;
            }

            $itemRelPath = $currentPath === '' ? $name : "{$currentPath}/{$name}";

            if ($item->isDir()) {
                $directories[] = [
                    'name' => $name,
                    'relative_path' => $itemRelPath,
                    'modified' => (int) ($item->getMTime() ?: 0),
                ];
            } elseif ($item->isFile()) {
                $files[] = $this->buildFileInfo($item->getPathname(), $itemRelPath);
            }
        }

        usort($directories, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        return [
            'current_path' => $currentPath,
            'breadcrumbs' => $breadcrumbs,
            'directories' => $directories,
            'files' => $files,
        ];
    }

    /**
     * Create a new slugified folder inside a safe parent directory.
     */
    public function createDirectory(string $parentRelativePath, string $folderName): string
    {
        $parentAbs = $this->resolveSafePath($parentRelativePath);
        if (! File::isDirectory($parentAbs)) {
            throw new InvalidArgumentException('Parent media directory does not exist.');
        }

        $slug = Str::slug(trim($folderName));
        if ($slug === '') {
            throw new InvalidArgumentException('Folder name must contain at least one alphanumeric character.');
        }

        $parentNorm = $this->normalizeRelativePath($parentRelativePath);
        $newRel = $parentNorm === '' ? $slug : "{$parentNorm}/{$slug}";
        $newAbs = $this->resolveSafePath($newRel);

        if (File::exists($newAbs) || is_link($newAbs)) {
            throw new InvalidArgumentException("Folder [{$slug}] already exists.");
        }

        File::makeDirectory($newAbs, 0755, false);

        return $newRel;
    }

    /**
     * Validate an uploaded file against extension allowlist, size limit,
     * executable/script signatures, and extension-to-MIME consistency.
     *
     * @throws InvalidArgumentException
     */
    public function validateUploadedFile(UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Invalid uploaded file.');
        }

        $allowedExts = array_map(
            'strtolower',
            (array) config('jangkeyte.media.allowed_extensions', [
                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip',
            ])
        );

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '' || ! in_array($ext, $allowedExts, true)) {
            throw new InvalidArgumentException("File extension [.{$ext}] is not allowed.");
        }

        $maxSizeKb = (int) config('jangkeyte.media.max_size_kb', 5120);
        $sizeBytes = (int) $file->getSize();
        if ($maxSizeKb > 0 && $sizeBytes > ($maxSizeKb * 1024)) {
            throw new InvalidArgumentException("File exceeds maximum allowed size of {$maxSizeKb} KB.");
        }

        $guessedExt = strtolower((string) ($file->guessExtension() ?? ''));
        if ($guessedExt !== '' && in_array($guessedExt, self::BLOCKED_GUESSED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Executable or script files are not allowed.');
        }

        $reportedMime = strtolower(trim(explode(';', (string) ($file->getMimeType() ?? ''))[0]));
        $contentMime = '';
        $realPath = $file->getRealPath();

        if (is_string($realPath) && $realPath !== '' && is_file($realPath) && class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $rawContentMime = $finfo->file($realPath);
            if (is_string($rawContentMime)) {
                $contentMime = strtolower(trim(explode(';', $rawContentMime)[0]));
            }
        }

        $mimesToCheck = array_values(array_unique(array_filter(
            [$reportedMime, $contentMime],
            static fn (string $mime): bool => $mime !== ''
        )));

        foreach ($mimesToCheck as $mime) {
            if (in_array($mime, self::BLOCKED_MIME_TYPES, true)) {
                throw new InvalidArgumentException("Detected file MIME type [{$mime}] is not allowed.");
            }
        }

        $detectedMime = $contentMime !== '' ? $contentMime : $reportedMime;

        if (in_array($ext, self::RASTER_IMAGE_EXTENSIONS, true)) {
            if ($mimesToCheck === []) {
                throw new InvalidArgumentException("Unable to verify image MIME type for [.{$ext}].");
            }

            foreach ($mimesToCheck as $mime) {
                if (! str_starts_with($mime, 'image/')) {
                    throw new InvalidArgumentException("File content MIME [{$mime}] does not match image extension [.{$ext}].");
                }
            }
        } elseif ($ext === 'svg') {
            if ($mimesToCheck === []) {
                throw new InvalidArgumentException('Unable to verify SVG MIME type.');
            }

            foreach ($mimesToCheck as $mime) {
                if (! in_array($mime, self::SVG_ALLOWED_MIME_TYPES, true)) {
                    throw new InvalidArgumentException("File content MIME [{$mime}] does not match SVG extension [.svg].");
                }
            }
        }

        if (
            in_array('text/plain', $mimesToCheck, true)
            || in_array('application/octet-stream', $mimesToCheck, true)
            || in_array($detectedMime, ['text/plain', 'application/octet-stream'], true)
        ) {
            if (is_string($realPath) && $realPath !== '' && is_file($realPath)) {
                $head = @file_get_contents($realPath, false, null, 0, 1024);
                if (is_string($head)) {
                    $trimmedHead = ltrim($head);
                    if (stripos($head, '<?php') !== false || str_contains($head, '<?=') || str_starts_with($trimmedHead, '#!/')) {
                        throw new InvalidArgumentException('Executable or script files are not allowed.');
                    }
                }
            }
        }

        return $ext;
    }

    /**
     * Validate and store uploaded files inside a safe media directory.
     *
     * @param  array<int, UploadedFile|mixed>  $files
     * @return array<int, array{name: string, relative_path: string, url: string, size: int, mime: string, is_image: bool, modified: int}>
     */
    public function uploadFiles(string $targetRelativePath, array $files): array
    {
        if ($files === []) {
            throw new InvalidArgumentException('No files were provided for upload.');
        }

        $targetAbs = $this->resolveSafePath($targetRelativePath);
        if (! File::isDirectory($targetAbs)) {
            throw new InvalidArgumentException('Target media directory does not exist.');
        }

        $targetNorm = $this->normalizeRelativePath($targetRelativePath);
        $validatedFiles = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                throw new InvalidArgumentException('Invalid uploaded file.');
            }

            $ext = $this->validateUploadedFile($file);
            $validatedFiles[] = [$file, $ext];
        }

        $uploaded = [];

        foreach ($validatedFiles as [$file, $ext]) {
            $origBase = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $baseSlug = Str::slug($origBase, '-') ?: 'file';
            $filename = "{$baseSlug}.{$ext}";

            while (File::exists($targetAbs . '/' . $filename)) {
                $filename = "{$baseSlug}-" . Str::lower(Str::random(6)) . ".{$ext}";
            }

            $file->move($targetAbs, $filename);

            $relPath = $targetNorm === '' ? $filename : "{$targetNorm}/{$filename}";
            $uploaded[] = $this->buildFileInfo($targetAbs . '/' . $filename, $relPath);
        }

        return $uploaded;
    }

    /**
     * Delete a file or directory safely within the media root.
     */
    public function deleteItem(string $relativePath): bool
    {
        $cleanRel = $this->normalizeRelativePath($relativePath);
        if ($cleanRel === '') {
            throw new InvalidArgumentException('Cannot delete the media root directory.');
        }

        $absPath = $this->resolveSafePath($relativePath);
        if ($absPath === $this->rootPath()) {
            throw new InvalidArgumentException('Cannot delete the media root directory.');
        }

        if (! File::exists($absPath) && ! is_link($absPath)) {
            return false;
        }

        if (File::isDirectory($absPath)) {
            return (bool) File::deleteDirectory($absPath);
        }

        return (bool) File::delete($absPath);
    }

    protected function rootPath(): string
    {
        $rootDir = trim((string) config('jangkeyte.media.root_dir', 'storage/uploads'), "/\\");
        if ($rootDir === '' || str_contains($rootDir, '..') || str_contains($rootDir, "\0")) {
            throw new InvalidArgumentException('Invalid media root directory configuration.');
        }

        $rawRoot = public_path($rootDir);

        if (! File::isDirectory($rawRoot)) {
            File::makeDirectory($rawRoot, 0755, true);
        }

        $real = realpath($rawRoot);

        return rtrim(str_replace('\\', '/', $real !== false ? $real : $rawRoot), '/');
    }

    protected function publicPrefix(): string
    {
        return trim(str_replace('\\', '/', (string) config('jangkeyte.media.root_dir', 'storage/uploads')), '/');
    }

    protected function normalizeRelativePath(string $relativePath): string
    {
        return trim((string) preg_replace('#/+#', '/', str_replace('\\', '/', $relativePath)), '/');
    }

    /**
     * @return array{name: string, relative_path: string, url: string, size: int, mime: string, is_image: bool, modified: int}
     */
    protected function buildFileInfo(string $absPath, string $relativePath): array
    {
        $name = basename($absPath);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = File::mimeType($absPath) ?: 'application/octet-stream';

        if ($ext === 'svg' && ! str_starts_with($mime, 'image/')) {
            $mime = 'image/svg+xml';
        }

        $isImage = in_array($ext, self::IMAGE_EXTENSIONS, true) || str_starts_with($mime, 'image/');
        $prefix = $this->publicPrefix();

        return [
            'name' => $name,
            'relative_path' => $relativePath,
            'url' => asset($prefix . '/' . ltrim($relativePath, '/')),
            'size' => (int) (filesize($absPath) ?: 0),
            'mime' => $mime,
            'is_image' => $isImage,
            'modified' => (int) (filemtime($absPath) ?: 0),
        ];
    }
}
