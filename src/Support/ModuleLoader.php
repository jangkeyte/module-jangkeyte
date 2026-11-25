<?php

namespace JangKeyte\Support;

use Illuminate\Support\Facades\File;

class ModuleLoader
{
    public function modulesPath(): string
    {
        return rtrim(str_replace('\\', '/', config('jangkeyte.modules_path', base_path('modules'))), '/');
    }

    public function statusesFile(): string
    {
        return str_replace('\\', '/', config('jangkeyte.statuses_file', base_path('modules_statuses.json')));
    }

    /**
     * @return array<string, bool>
     */
    public function statuses(): array
    {
        $file = $this->statusesFile();
        if (! File::isFile($file)) {
            return [];
        }

        $decoded = json_decode(File::get($file), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setStatus(string $moduleName, bool $enabled = true): void
    {
        $file = $this->statusesFile();
        $statuses = $this->statuses();
        $statuses[$moduleName] = $enabled;
        ksort($statuses);

        File::ensureDirectoryExists(dirname($file));
        File::put(
            $file,
            json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );
    }

    public function removeStatus(string $moduleName): void
    {
        $file = $this->statusesFile();
        $statuses = $this->statuses();
        unset($statuses[$moduleName]);
        ksort($statuses);

        File::ensureDirectoryExists(dirname($file));
        File::put(
            $file,
            $statuses === []
                ? "{}\n"
                : json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );
    }

    /**
     * Discover all module directories and their enabled state.
     *
     * @return array<string, array{name: string, path: string, enabled: bool, provider: string}>
     */
    public function allModules(): array
    {
        $path = $this->modulesPath();
        if (! File::isDirectory($path)) {
            return [];
        }

        $statuses = $this->statuses();
        $namespace = trim(config('jangkeyte.modules_namespace', 'Modules'), '\\');
        $modules = [];

        foreach (File::directories($path) as $dir) {
            $normalizedDir = str_replace('\\', '/', $dir);
            $name = basename($normalizedDir);
            $provider = "{$namespace}\\{$name}\\Providers\\{$name}ServiceProvider";

            $modules[$name] = [
                'name' => $name,
                'path' => $normalizedDir,
                'enabled' => array_key_exists($name, $statuses) ? (bool) $statuses[$name] : true,
                'provider' => $provider,
            ];
        }

        ksort($modules);

        return $modules;
    }

    /**
     * @return array<string, array{name: string, path: string, enabled: bool, provider: string}>
     */
    public function enabledModules(): array
    {
        return array_filter(
            $this->allModules(),
            static fn (array $mod): bool => $mod['enabled']
        );
    }
}
