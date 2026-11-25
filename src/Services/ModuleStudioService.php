<?php

namespace JangKeyte\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JangKeyte\Models\Permission;
use JangKeyte\Services\Generator\CrudDefinition;
use JangKeyte\Services\Generator\FieldDefinition;
use JangKeyte\Support\ModuleLoader;
use ReflectionProperty;

class ModuleStudioService
{
    /**
     * @var array<int, string>
     */
    public const CORE_PROTECTED_TABLES = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'permissions',
        'roles',
        'users_permissions',
        'users_roles',
        'roles_permissions',
        'settings',
        'migrations',
    ];

    /**
     * @var array<int, string>
     */
    public const CORE_PROTECTED_PERMISSION_SLUGS_KEBAB = [
        'dashboard',
        'user',
        'role',
        'permission',
        'setting',
        'media',
        'module',
    ];

    public function __construct(
        protected ModuleLoader $moduleLoader,
        protected PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * List all discovered modules along with their Database/Schemas/*.json manifests.
     *
     * @return array<string, array{
     *     name: string,
     *     path: string,
     *     enabled: bool,
     *     provider: string,
     *     schemas: array<int, array<string, mixed>>
     * }>
     */
    public function listModulesWithSchemas(): array
    {
        $modules = $this->moduleLoader->allModules();

        foreach ($modules as $name => $mod) {
            $schemasDir = $mod['path'] . '/Database/Schemas';
            $schemas = [];

            if (File::isDirectory($schemasDir)) {
                $files = File::glob($schemasDir . '/*.json') ?: [];
                sort($files);

                foreach ($files as $file) {
                    $decoded = json_decode(File::get($file), true);
                    if (! is_array($decoded)) {
                        continue;
                    }

                    $routeIndex = $decoded['route_index'] ?? null;
                    $decoded['route_available'] = (bool) $mod['enabled']
                        && is_string($routeIndex)
                        && $routeIndex !== ''
                        && Route::has($routeIndex);

                    $schemas[] = $decoded;
                }
            }

            $modules[$name]['schemas'] = $schemas;
        }

        return $modules;
    }

    public function toggleModule(string $moduleName): bool
    {
        $cleanName = $this->assertValidModuleName($moduleName);
        $this->resolveExistingModuleDir($cleanName);

        $modules = $this->moduleLoader->allModules();
        $current = (bool) ($modules[$cleanName]['enabled'] ?? true);
        $next = ! $current;

        $this->moduleLoader->setStatus($cleanName, $next);

        return $next;
    }

    public function deleteModule(string $moduleName, bool $dropTables = false): void
    {
        $cleanName = $this->assertValidModuleName($moduleName);
        $moduleDir = $this->resolveExistingModuleDir($cleanName);

        if ($dropTables) {
            $metadata = $this->collectModuleMetadata($moduleDir);
            $orderedTables = $this->sortTablesForDrop($metadata['tables'], $metadata['table_dependencies']);

            if ($orderedTables !== []) {
                Schema::disableForeignKeyConstraints();
                try {
                    foreach ($orderedTables as $table) {
                        Schema::dropIfExists($table);
                    }
                } finally {
                    Schema::enableForeignKeyConstraints();
                }
            }

            $this->deleteMigrationRecords($orderedTables, $metadata['migration_names'], $metadata['migration_files']);
            $this->deleteModulePermissions($metadata['permission_kebabs']);
        }

        File::deleteDirectory($moduleDir);
        $this->moduleLoader->removeStatus($cleanName);
    }

    protected function assertValidModuleName(string $moduleName): string
    {
        $clean = trim($moduleName);
        if (preg_match(CrudDefinition::STUDLY_REGEX, $clean) !== 1) {
            throw new InvalidArgumentException("Invalid module name [{$moduleName}].");
        }

        return $clean;
    }

    protected function resolveExistingModuleDir(string $moduleName): string
    {
        $cleanName = $this->assertValidModuleName($moduleName);

        $rootRaw = $this->moduleLoader->modulesPath();
        $rootReal = realpath($rootRaw);
        if ($rootReal === false) {
            throw new InvalidArgumentException("Modules directory does not exist.");
        }

        $rootNorm = rtrim(str_replace('\\', '/', $rootReal), '/');
        $candidate = $rootNorm . '/' . $cleanName;

        if (is_link($candidate)) {
            throw new InvalidArgumentException("Module [{$cleanName}] cannot be a symlink.");
        }

        if (! File::isDirectory($candidate)) {
            throw new InvalidArgumentException("Module [{$cleanName}] not found.");
        }

        $real = realpath($candidate);
        if ($real === false) {
            throw new InvalidArgumentException("Module [{$cleanName}] could not be resolved.");
        }

        $realNorm = rtrim(str_replace('\\', '/', $real), '/');
        if ($realNorm !== $rootNorm . '/' . $cleanName) {
            throw new InvalidArgumentException("Module [{$cleanName}] is outside the modules directory.");
        }

        return $realNorm;
    }

    /**
     * Collect tables, FK dependencies, migrations, and permission kebab slugs BEFORE deleting module files.
     *
     * @return array{
     *     tables: array<int, string>,
     *     table_dependencies: array<string, array<int, string>>,
     *     migration_names: array<int, string>,
     *     migration_files: array<int, string>,
     *     permission_kebabs: array<int, string>
     * }
     */
    protected function collectModuleMetadata(string $moduleDir): array
    {
        $tables = [];
        $dependencies = [];
        $permissionKebabs = [];

        $schemasDir = $moduleDir . '/Database/Schemas';
        if (File::isDirectory($schemasDir)) {
            $schemaFiles = File::glob($schemasDir . '/*.json') ?: [];
            sort($schemaFiles);

            foreach ($schemaFiles as $file) {
                $decoded = json_decode(File::get($file), true);
                if (! is_array($decoded)) {
                    continue;
                }

                $table = is_string($decoded['table'] ?? null) ? trim($decoded['table']) : '';
                if (
                    $table !== ''
                    && preg_match(FieldDefinition::IDENTIFIER_REGEX, $table) === 1
                    && ! in_array($table, self::CORE_PROTECTED_TABLES, true)
                ) {
                    $tables[$table] = $table;
                    $dependencies[$table] = $dependencies[$table] ?? [];

                    $fields = is_array($decoded['fields'] ?? null) ? $decoded['fields'] : [];
                    foreach ($fields as $fieldData) {
                        if (! is_array($fieldData)) {
                            continue;
                        }
                        if (($fieldData['type'] ?? null) !== 'belongsTo') {
                            continue;
                        }
                        try {
                            $field = FieldDefinition::fromArray($fieldData);
                            $refTable = $field->relatedTableName();
                            if ($refTable !== null && $refTable !== $table) {
                                $dependencies[$table][] = $refTable;
                            }
                        } catch (\Throwable) {
                            // Ignore malformed legacy field entries safely.
                        }
                    }
                }

                $kebab = null;
                if (is_string($decoded['model_kebab'] ?? null) && trim($decoded['model_kebab']) !== '') {
                    $kebab = trim($decoded['model_kebab']);
                } elseif (is_string($decoded['model'] ?? null) && trim($decoded['model']) !== '') {
                    $kebab = Str::kebab(trim($decoded['model']));
                } elseif ($table !== '') {
                    $kebab = Str::kebab(Str::singular($table));
                }

                if ($kebab !== null && $kebab !== '' && ! in_array($kebab, self::CORE_PROTECTED_PERMISSION_SLUGS_KEBAB, true)) {
                    $permissionKebabs[$kebab] = $kebab;
                }
            }
        }

        $modelsDir = $moduleDir . '/Models';
        if (File::isDirectory($modelsDir)) {
            $modelFiles = File::glob($modelsDir . '/*.php') ?: [];
            foreach ($modelFiles as $modelFile) {
                $modelName = pathinfo($modelFile, PATHINFO_FILENAME);
                if (preg_match(CrudDefinition::STUDLY_REGEX, $modelName) === 1) {
                    $kebab = Str::kebab($modelName);
                    if (! in_array($kebab, self::CORE_PROTECTED_PERMISSION_SLUGS_KEBAB, true)) {
                        $permissionKebabs[$kebab] = $kebab;
                    }
                }
            }
        }

        $migrationNames = [];
        $migrationFiles = [];
        $migrationsDir = $moduleDir . '/Database/Migrations';
        if (File::isDirectory($migrationsDir)) {
            $files = File::glob($migrationsDir . '/*.php') ?: [];
            sort($files);
            foreach ($files as $file) {
                $migrationFiles[] = str_replace('\\', '/', $file);
                $migrationNames[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return [
            'tables' => array_values($tables),
            'table_dependencies' => $dependencies,
            'migration_names' => array_values(array_unique($migrationNames)),
            'migration_files' => $migrationFiles,
            'permission_kebabs' => array_values($permissionKebabs),
        ];
    }

    /**
     * Order tables so referencing child tables are dropped before referenced parent tables.
     *
     * @param  array<int, string>  $tables
     * @param  array<string, array<int, string>>  $dependencies
     * @return array<int, string>
     */
    protected function sortTablesForDrop(array $tables, array $dependencies): array
    {
        if (count($tables) <= 1) {
            return $tables;
        }

        sort($tables);
        $tableSet = array_flip($tables);
        $inDegree = array_fill_keys($tables, 0);
        $childToParents = array_fill_keys($tables, []);

        foreach ($tables as $child) {
            foreach (array_unique($dependencies[$child] ?? []) as $parent) {
                if (isset($tableSet[$parent]) && $parent !== $child) {
                    $childToParents[$child][] = $parent;
                    $inDegree[$parent]++;
                }
            }
        }

        $queue = [];
        foreach ($tables as $table) {
            if ($inDegree[$table] === 0) {
                $queue[] = $table;
            }
        }

        $ordered = [];
        while ($queue !== []) {
            $current = array_shift($queue);
            $ordered[] = $current;

            foreach ($childToParents[$current] as $parent) {
                $inDegree[$parent]--;
                if ($inDegree[$parent] === 0) {
                    $queue[] = $parent;
                }
            }
        }

        // Append any cyclic remainders deterministically
        foreach ($tables as $table) {
            if (! in_array($table, $ordered, true)) {
                $ordered[] = $table;
            }
        }

        return $ordered;
    }

    /**
     * @param  array<int, string>  $tables
     * @param  array<int, string>  $migrationNames
     * @param  array<int, string>  $migrationFiles
     */
    protected function deleteMigrationRecords(array $tables, array $migrationNames, array $migrationFiles): void
    {
        if (Schema::hasTable('migrations')) {
            $toDelete = $migrationNames;

            if ($tables !== []) {
                $dbMigrations = DB::table('migrations')->pluck('migration');
                foreach ($tables as $table) {
                    $pattern = '/^\d{4}_\d{2}_\d{2}_\d{6}_create_' . preg_quote($table, '/') . '_table$/';
                    foreach ($dbMigrations as $migName) {
                        if (is_string($migName) && preg_match($pattern, $migName) === 1) {
                            $toDelete[] = $migName;
                        }
                    }
                }
            }

            $toDelete = array_values(array_unique(array_filter($toDelete)));
            if ($toDelete !== []) {
                DB::table('migrations')->whereIn('migration', $toDelete)->delete();
            }
        }

        $this->invalidateMigratorPathCache($migrationFiles);
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function invalidateMigratorPathCache(array $paths): void
    {
        if (! class_exists(Migrator::class) || $paths === []) {
            return;
        }

        try {
            $prop = new ReflectionProperty(Migrator::class, 'requiredPathCache');
            $prop->setAccessible(true);
            $cache = $prop->getValue();
            if (! is_array($cache)) {
                return;
            }

            $normalizedTargets = [];
            foreach ($paths as $path) {
                $normalizedTargets[str_replace('\\', '/', $path)] = true;
                $real = realpath($path);
                if (is_string($real)) {
                    $normalizedTargets[str_replace('\\', '/', $real)] = true;
                }
            }

            foreach (array_keys($cache) as $cachedKey) {
                $normKey = str_replace('\\', '/', (string) $cachedKey);
                if (isset($normalizedTargets[$normKey])) {
                    unset($cache[$cachedKey]);
                }
            }

            $prop->setValue(null, $cache);
        } catch (\Throwable) {
            // Safe fallback if Migrator internals change.
        }
    }

    /**
     * @param  array<int, string>  $permissionKebabs
     */
    protected function deleteModulePermissions(array $permissionKebabs): void
    {
        $slugs = [];
        foreach ($permissionKebabs as $kebab) {
            if (in_array($kebab, self::CORE_PROTECTED_PERMISSION_SLUGS_KEBAB, true)) {
                continue;
            }

            foreach (['browse', 'read', 'add', 'edit', 'delete'] as $action) {
                $slugs[] = "{$action}-{$kebab}";
            }
        }

        $slugs = array_values(array_unique($slugs));

        if ($slugs !== [] && Schema::hasTable('permissions')) {
            Permission::whereIn('slug', $slugs)->get()->each(function (Permission $permission): void {
                $permission->roles()->detach();
                $permission->users()->detach();
                $permission->delete();
            });
        }

        $this->permissionRegistrar->forgetCachedPermissions();
    }
}
