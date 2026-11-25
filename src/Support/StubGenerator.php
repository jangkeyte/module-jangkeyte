<?php

namespace JangKeyte\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

class StubGenerator
{
    public function render(string $stubPath, array $replacements = []): string
    {
        if (! File::isFile($stubPath)) {
            throw new RuntimeException("Stub file does not exist: {$stubPath}");
        }

        $content = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $content = str_replace("{{ {$key} }}", (string) $value, $content);
        }

        return $content;
    }

    public function generateToFile(string $stubPath, string $targetFile, array $replacements = [], bool $overwrite = false): bool
    {
        if (File::isFile($targetFile) && ! $overwrite) {
            return false;
        }

        $dir = dirname($targetFile);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $rendered = $this->render($stubPath, $replacements);
        File::put($targetFile, $rendered);

        return true;
    }
}
