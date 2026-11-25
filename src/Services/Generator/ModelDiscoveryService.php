<?php

namespace JangKeyte\Services\Generator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JangKeyte\Support\ModuleLoader;
use ReflectionClass;
use SplFileInfo;

class ModelDiscoveryService
{
    public function __construct(protected ModuleLoader $moduleLoader) {}

    /**
     * Discover all usable Eloquent models in Core (JangKeyte\Models) and enabled Modules.
     *
     * @return array<int, array{class: string, label: string, table: string, columns: array<int, string>}>
     */
    public function availableModels(): array
    {
        $models = [];

        $coreModelsDir = dirname(__DIR__, 2) . '/Models';
        foreach ($this->scanDirectory($coreModelsDir, 'JangKeyte\\Models\\', 'Core') as $item) {
            $models[] = $item;
        }

        $modulesNs = trim((string) config('jangkeyte.modules_namespace', 'Modules'), '\\');
        foreach ($this->moduleLoader->enabledModules() as $module) {
            $modModelsDir = $module['path'] . '/Models';
            $nsPrefix = "{$modulesNs}\\{$module['name']}\\Models\\";
            foreach ($this->scanDirectory($modModelsDir, $nsPrefix, $module['name']) as $item) {
                $models[] = $item;
            }
        }

        return $models;
    }

    /**
     * @return array<int, array{class: string, label: string, table: string, columns: array<int, string>}>
     */
    protected function scanDirectory(string $directory, string $namespacePrefix, string $scopeLabel): array
    {
        if (! File::isDirectory($directory)) {
            return [];
        }

        $files = File::files($directory);
        usort($files, static fn (SplFileInfo $a, SplFileInfo $b): int => strcmp($a->getFilename(), $b->getFilename()));

        $discovered = [];
        foreach ($files as $file) {
            $info = $this->inspectModelFile($file, $namespacePrefix, $scopeLabel);
            if ($info !== null) {
                $discovered[] = $info;
            }
        }

        return $discovered;
    }

    /**
     * @return array{class: string, label: string, table: string, columns: array<int, string>}|null
     */
    protected function inspectModelFile(SplFileInfo $file, string $namespacePrefix, string $scopeLabel): ?array
    {
        if (strtolower($file->getExtension()) !== 'php') {
            return null;
        }

        $shortName = $file->getFilenameWithoutExtension();
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $shortName) !== 1) {
            return null;
        }

        $fqcn = $namespacePrefix . $shortName;
        $expectedNamespace = trim($namespacePrefix, '\\');

        if (! class_exists($fqcn)) {
            $contents = File::get($file->getPathname());
            $nsPattern = '/^\s*namespace\s+' . preg_quote($expectedNamespace, '/') . '\s*;/m';
            $classPattern = '/^\s*(?:final\s+|readonly\s+)*class\s+' . preg_quote($shortName, '/') . '\b/m';

            if (preg_match($nsPattern, $contents) !== 1 || preg_match($classPattern, $contents) !== 1) {
                return null;
            }

            try {
                require_once $file->getPathname();
            } catch (\Throwable) {
                return null;
            }

            if (! class_exists($fqcn)) {
                return null;
            }
        }

        try {
            $reflection = new ReflectionClass($fqcn);
        } catch (\Throwable) {
            return null;
        }

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait() || ! $reflection->isSubclassOf(Model::class)) {
            return null;
        }

        $instance = $this->instantiateSafely($reflection);
        if ($instance === null) {
            return null;
        }

        $table = $instance->getTable();
        $columns = $this->resolveColumns($instance, $table);

        return [
            'class' => $fqcn,
            'label' => "{$shortName} ({$scopeLabel})",
            'table' => $table,
            'columns' => $columns,
        ];
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     */
    protected function instantiateSafely(ReflectionClass $reflection): ?Model
    {
        try {
            $constructor = $reflection->getConstructor();
            $instance = ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0)
                ? $reflection->newInstance()
                : $reflection->newInstanceWithoutConstructor();

            return $instance instanceof Model ? $instance : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function resolveColumns(Model $instance, string $table): array
    {
        $columns = [];

        try {
            $schema = Schema::connection($instance->getConnectionName());
            if ($schema->hasTable($table)) {
                $columns = $schema->getColumnListing($table);
            }
        } catch (\Throwable) {
            $columns = [];
        }

        if ($columns !== []) {
            $hidden = $instance->getHidden();

            return array_values(array_diff($columns, $hidden));
        }

        return array_values(array_diff($instance->getFillable(), $instance->getHidden()));
    }
}
