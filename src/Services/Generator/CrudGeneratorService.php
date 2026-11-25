<?php

namespace JangKeyte\Services\Generator;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JangKeyte\Services\PermissionRegistrar;
use JangKeyte\Support\ModuleLoader;
use JangKeyte\Support\StubGenerator;
use ReflectionProperty;

class CrudGeneratorService
{
    public function __construct(
        protected StubGenerator $stubs,
        protected ModuleLoader $moduleLoader,
        protected PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @return array{files: array<string, string>, table: string, url: string}
     */
    public function generate(CrudDefinition $def): array
    {
        $stubDir = dirname(__DIR__, 3) . '/resources/stubs';
        $moduleDir = $this->moduleLoader->modulesPath() . '/' . $def->moduleName;
        $modulesNs = trim((string) config('jangkeyte.modules_namespace', 'Modules'), '\\');
        $moduleNamespace = "{$modulesNs}\\{$def->moduleName}";
        $tableName = $def->tableName();
        $modelSnake = $def->modelSnake();
        $modelKebab = $def->modelKebab();

        $generatedFiles = [];

        // 1. Ensure Module skeleton exists and mark module enabled
        foreach ($this->ensureModuleSkeleton($stubDir, $moduleDir, $def->moduleName, $moduleNamespace) as $key => $path) {
            $generatedFiles[$key] = $path;
        }

        // 2. Build replacement tokens for v2.1 stubs
        $replacements = $this->buildReplacements($def, $moduleNamespace);

        // 3. Migration (replace existing *_create_{table}_table.php in-place when force === true)
        $migrationsDir = "{$moduleDir}/Database/Migrations";
        File::ensureDirectoryExists($migrationsDir);

        $existingMigrations = File::glob("{$migrationsDir}/*_create_{$tableName}_table.php") ?: [];
        sort($existingMigrations);
        $oldMigrationNames = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $existingMigrations
        );

        $migrationFile = null;
        if ($existingMigrations === []) {
            $migrationFile = "{$migrationsDir}/" . date('Y_m_d_His') . "_create_{$tableName}_table.php";
            $this->stubs->generateToFile("{$stubDir}/migration.stub", $migrationFile, $replacements, true);
            $generatedFiles['Migration'] = $migrationFile;
        } elseif ($def->force) {
            $migrationFile = $existingMigrations[0];
            foreach (array_slice($existingMigrations, 1) as $duplicate) {
                File::delete($duplicate);
            }
            $this->stubs->generateToFile("{$stubDir}/migration.stub", $migrationFile, $replacements, true);
            $generatedFiles['Migration'] = $migrationFile;
        } else {
            $migrationFile = $existingMigrations[0];
        }

        // 4. Model, Filter, Repositories, Service, Request, Controller, Route, Views
        $targets = [
            'Model' => ["{$stubDir}/model.stub", "{$moduleDir}/Models/{$def->modelName}.php"],
            'Filter' => ["{$stubDir}/filter.stub", "{$moduleDir}/Filters/{$def->modelName}Filter.php"],
            'RepositoryInterface' => ["{$stubDir}/repository.interface.stub", "{$moduleDir}/Repositories/{$def->modelName}/{$def->modelName}RepositoryInterface.php"],
            'Repository' => ["{$stubDir}/repository.stub", "{$moduleDir}/Repositories/{$def->modelName}/{$def->modelName}Repository.php"],
            'Service' => ["{$stubDir}/service.stub", "{$moduleDir}/Services/{$def->modelName}Service.php"],
            'FormRequest' => ["{$stubDir}/request.stub", "{$moduleDir}/Http/Requests/{$def->modelName}/Save{$def->modelName}Request.php"],
            'Controller' => ["{$stubDir}/controller.stub", "{$moduleDir}/Http/Controllers/{$def->modelName}/{$def->modelName}Controller.php"],
            'Routes' => ["{$stubDir}/route.stub", "{$moduleDir}/Routes/{$modelSnake}.php"],
            'Index View' => ["{$stubDir}/view.index.stub", "{$moduleDir}/Resources/views/{$modelSnake}/index.blade.php"],
            'Form View' => ["{$stubDir}/view.form.stub", "{$moduleDir}/Resources/views/{$modelSnake}/form.blade.php"],
        ];

        foreach ($targets as $label => [$stubFile, $destFile]) {
            $written = $this->stubs->generateToFile($stubFile, $destFile, $replacements, $def->force);
            if ($written || File::isFile($destFile)) {
                $generatedFiles[$label] = $destFile;
            }
        }

        // 5. Update Config/menu.php cleanly using short array syntax
        $this->upsertMenuEntry(
            "{$moduleDir}/Config/menu.php",
            $modelSnake,
            $def->headlinePlural(),
            $def->headline(),
            $def->icon,
            $modelKebab
        );

        // 6. Persist Schema Manifest JSON
        $schemaPath = $this->writeSchemaManifest($moduleDir, $def);
        $generatedFiles['Schema'] = $schemaPath;

        // 7. Run Migrations & Seed RBAC Permissions if requested
        if ($def->runMigrations) {
            if ($def->force) {
                $this->resetMigrationState($tableName, $oldMigrationNames, $existingMigrations, $migrationFile);
            }

            $absMigDir = $this->toAbsoluteForwardPath($migrationsDir);
            Artisan::call('migrate', [
                '--path' => [$absMigDir],
                '--realpath' => true,
                '--force' => true,
            ]);

            $this->permissionRegistrar->ensureCrudPermissionsFor($def->modelName);
        }

        return [
            'files' => $generatedFiles,
            'table' => $tableName,
            'url' => "/admin/{$modelKebab}",
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function ensureModuleSkeleton(
        string $stubDir,
        string $moduleDir,
        string $moduleName,
        string $moduleNamespace,
    ): array {
        $replacements = [
            'MODULE_NAMESPACE' => $moduleNamespace,
            'MODULE_NAME' => $moduleName,
            'MODULE_KEY' => strtolower($moduleName),
        ];

        $created = [];

        $providerFile = "{$moduleDir}/Providers/{$moduleName}ServiceProvider.php";
        if (! File::isFile($providerFile)) {
            $this->stubs->generateToFile("{$stubDir}/module.provider.stub", $providerFile, $replacements, true);
            $created['Module Provider'] = $providerFile;
        }

        $configFile = "{$moduleDir}/Config/config.php";
        if (! File::isFile($configFile)) {
            $this->stubs->generateToFile("{$stubDir}/module.config.stub", $configFile, $replacements, true);
            $created['Module Config'] = $configFile;
        }

        $menuFile = "{$moduleDir}/Config/menu.php";
        if (! File::isFile($menuFile)) {
            $this->stubs->generateToFile("{$stubDir}/module.menu.stub", $menuFile, $replacements, true);
            $created['Module Menu'] = $menuFile;
        }

        File::ensureDirectoryExists(dirname($this->moduleLoader->statusesFile()));
        $this->moduleLoader->setStatus($moduleName, true);

        return $created;
    }

    /**
     * @return array<string, string>
     */
    protected function buildReplacements(CrudDefinition $def, string $moduleNamespace): array
    {
        $tableName = $def->tableName();
        $modelSnake = $def->modelSnake();
        $modelKebab = $def->modelKebab();
        $modelName = $def->modelName;

        $migrations = [];
        $fillables = [];
        $casts = [];
        $searchable = [];
        $belongsToSearch = [];
        $rules = [];
        $fileFields = [];
        $booleanFields = [];
        $belongsToMethods = [];
        $withRelations = [];
        $relationOptionsLines = [];
        $headers = [];
        $cells = [];
        $inputs = [];
        $exportHeaders = ["'ID'"];
        $exportCellExpressions = ["\$item->id"];

        $usedRelationMethods = [];

        foreach ($def->fields as $field) {
            $col = $field->name;
            $type = $field->type;
            $label = Str::headline($col);
            $fillables[] = "'{$col}'";
            $reqRule = $field->nullable ? 'nullable' : 'required';

            $relMethod = null;
            if ($field->isBelongsTo()) {
                $baseRel = $field->relationMethodName() ?? Str::camel($col) . 'Relation';
                $relMethod = $baseRel;
                $suffix = 2;
                while (isset($usedRelationMethods[$relMethod])) {
                    $relMethod = $baseRel . $suffix;
                    $suffix++;
                }
                $usedRelationMethods[$relMethod] = true;
            }

            // 1. Migration, Casts, Validation Rules, Model Relations
            switch ($type) {
                case 'belongsTo':
                    $relTable = $field->relatedTableName() ?? 'users';
                    $relKey = $field->relatedKeyName();
                    $relModel = ltrim((string) $field->relatedModel, '\\');
                    $displayCol = $field->relatedDisplayColumn ?? 'name';

                    $migrations[] = $field->nullable
                        ? "\$table->foreignId('{$col}')->nullable()->constrained('{$relTable}')->nullOnDelete();"
                        : "\$table->foreignId('{$col}')->constrained('{$relTable}')->cascadeOnDelete();";
                    $casts[] = "'{$col}' => 'integer'";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'integer', 'exists:{$relTable},{$relKey}']";

                    $belongsToMethods[] = <<<PHP
    public function {$relMethod}(): BelongsTo
    {
        return \$this->belongsTo(\\{$relModel}::class, '{$col}');
    }
PHP;
                    $withRelations[] = "'{$relMethod}'";
                    $relationOptionsLines[] = "'{$col}' => class_exists(\\{$relModel}::class)\n                ? \\{$relModel}::query()->orderBy('{$displayCol}')->pluck('{$displayCol}', '{$relKey}')->all()\n                : [],";
                    $belongsToSearch[] = [$relMethod, $displayCol];
                    break;

                case 'integer':
                    $migrations[] = $field->nullable
                        ? "\$table->integer('{$col}')->nullable();"
                        : "\$table->integer('{$col}')->default(0);";
                    $casts[] = "'{$col}' => 'integer'";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'integer']";
                    break;

                case 'decimal':
                    $migrations[] = $field->nullable
                        ? "\$table->decimal('{$col}', 15, 2)->nullable();"
                        : "\$table->decimal('{$col}', 15, 2)->default(0);";
                    $casts[] = "'{$col}' => 'decimal:2'";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'numeric']";
                    break;

                case 'boolean':
                    $migrations[] = $field->nullable
                        ? "\$table->boolean('{$col}')->nullable();"
                        : "\$table->boolean('{$col}')->default(true);";
                    $casts[] = "'{$col}' => 'boolean'";
                    $rules[] = "'{$col}' => ['nullable', 'boolean']";
                    $booleanFields[] = "'{$col}'";
                    break;

                case 'date':
                    $migrations[] = $field->nullable
                        ? "\$table->date('{$col}')->nullable();"
                        : "\$table->date('{$col}');";
                    $casts[] = "'{$col}' => 'date'";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'date']";
                    break;

                case 'datetime':
                    $migrations[] = $field->nullable
                        ? "\$table->dateTime('{$col}')->nullable();"
                        : "\$table->dateTime('{$col}');";
                    $casts[] = "'{$col}' => 'datetime'";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'date']";
                    break;

                case 'text':
                    $migrations[] = $field->nullable
                        ? "\$table->text('{$col}')->nullable();"
                        : "\$table->text('{$col}');";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'string']";
                    $searchable[] = $col;
                    break;

                case 'richtext':
                    $migrations[] = $field->nullable
                        ? "\$table->longText('{$col}')->nullable();"
                        : "\$table->longText('{$col}');";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'string']";
                    $searchable[] = $col;
                    break;

                case 'image':
                    $migrations[] = $field->nullable
                        ? "\$table->string('{$col}')->nullable();"
                        : "\$table->string('{$col}');";
                    $imageReqRule = $field->nullable
                        ? "'nullable'"
                        : "'nullable', (\$this->isMethod('post') ? 'required_without:{$col}_media_path' : 'nullable')";
                    $rules[] = $this->uploadedFileValidationRule(
                        $col,
                        $imageReqRule,
                        "'mimes:jpg,jpeg,png,gif,webp,svg'"
                    );
                    $rules[] = $this->mediaPathValidationRule($col);
                    $fileFields[] = "'{$col}'";
                    break;

                case 'file':
                    $migrations[] = $field->nullable
                        ? "\$table->string('{$col}')->nullable();"
                        : "\$table->string('{$col}');";
                    $fileReqRule = $field->nullable
                        ? "'nullable'"
                        : "'nullable', (\$this->isMethod('post') ? 'required_without:{$col}_media_path' : 'nullable')";
                    $rules[] = $this->uploadedFileValidationRule(
                        $col,
                        $fileReqRule,
                        "'mimes:' . implode(',', (array) config('jangkeyte.media.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']))"
                    );
                    $rules[] = $this->mediaPathValidationRule($col);
                    $fileFields[] = "'{$col}'";
                    break;

                case 'email':
                    $migrations[] = $field->nullable
                        ? "\$table->string('{$col}')->nullable();"
                        : "\$table->string('{$col}');";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'email', 'max:191']";
                    $searchable[] = $col;
                    break;

                default:
                    $migrations[] = $field->nullable
                        ? "\$table->string('{$col}')->nullable();"
                        : "\$table->string('{$col}');";
                    $rules[] = "'{$col}' => ['{$reqRule}', 'string', 'max:255']";
                    $searchable[] = $col;
                    break;
            }

            // 2. Index Table Headers & Cells
            $headerLabel = $field->isBelongsTo() && str_ends_with($col, '_id')
                ? Str::headline(substr($col, 0, -3))
                : $label;

            if ($field->sortable) {
                $headers[] = $this->renderSortableHeader($modelSnake, $col, $headerLabel);
            } else {
                $headers[] = "<th>{{ __('{$headerLabel}') }}</th>";
            }

            $cells[] = $this->renderTableCell($field, $relMethod, $tableName);

            // 3. Export CSV Headers & Cell Expressions
            $exportHeaderSafe = str_replace("'", "\\'", $headerLabel);
            $exportHeaders[] = "'{$exportHeaderSafe}'";
            $exportCellExpressions[] = $this->renderExportExpression($field, $relMethod);

            // 4. Form Input HTML
            $inputs[] = $this->renderFormInput($field, $headerLabel, $tableName);
        }

        if ($def->softDeletes) {
            $migrations[] = '$table->softDeletes();';
        }

        // Model Imports & Traits
        $modelImports = [
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use Illuminate\Database\Eloquent\Model;',
            'use JangKeyte\Traits\Filterable;',
        ];
        $modelTraits = "Filterable, HasFactory";

        if ($def->hasBelongsTo()) {
            $modelImports[] = 'use Illuminate\Database\Eloquent\Relations\BelongsTo;';
        }
        if ($def->softDeletes) {
            $modelImports[] = 'use Illuminate\Database\Eloquent\SoftDeletes;';
            $modelTraits .= ";\n    use SoftDeletes";
        }
        sort($modelImports);

        // Filter search conditions
        $filterConditions = [];
        $hasFirstCondition = false;
        foreach ($searchable as $col) {
            $method = $hasFirstCondition ? 'orWhere' : 'where';
            $filterConditions[] = "\$query->{$method}('{$col}', 'like', \"%{\$term}%\");";
            $hasFirstCondition = true;
        }
        foreach ($belongsToSearch as [$relMethod, $displayCol]) {
            $method = $hasFirstCondition ? 'orWhereHas' : 'whereHas';
            $filterConditions[] = "\$query->{$method}('{$relMethod}', static function (Builder \$relQuery) use (\$term): void {\n                \$relQuery->where('{$displayCol}', 'like', \"%{\$term}%\");\n            });";
            $hasFirstCondition = true;
        }
        if ($filterConditions === []) {
            $filterConditions[] = '$query->whereKey($term);';
        }

        // Relation options body
        $relationOptionsBody = $relationOptionsLines === []
            ? 'return [];'
            : "return [\n            " . implode("\n            ", $relationOptionsLines) . "\n        ];";

        // Export & SoftDelete fragments
        $exportHeaders[] = "'Created At'";
        $exportCellExpressions[] = "\$item->created_at?->format('Y-m-d H:i:s')";

        $serviceImports = $def->exportable ? "\nuse Symfony\\Component\\HttpFoundation\\StreamedResponse;" : '';
        $softDeleteServiceMethods = $def->softDeletes ? $this->renderSoftDeleteServiceMethods() : '';
        $exportMethod = $def->exportable
            ? $this->renderExportServiceMethod($modelName, $tableName, $exportHeaders, $exportCellExpressions)
            : '';

        $controllerImports = $def->exportable ? "\nuse Symfony\\Component\\HttpFoundation\\StreamedResponse;" : '';
        $controllerExtraMethods = $this->renderControllerExtraMethods($modelSnake, $def->softDeletes, $def->exportable);

        $exportRoute = $def->exportable
            ? "\n    Route::get('/export', [{$modelName}Controller::class, 'export'])\n        ->middleware('permission:browse-{$modelKebab}')\n        ->name('export');"
            : '';

        $softDeleteRoutes = $def->softDeletes
            ? "\n    Route::post('/{id}/restore', [{$modelName}Controller::class, 'restore'])\n        ->whereNumber('id')\n        ->middleware('permission:edit-{$modelKebab}')\n        ->name('restore');\n    Route::delete('/{id}/force-destroy', [{$modelName}Controller::class, 'forceDestroy'])\n        ->whereNumber('id')\n        ->middleware('permission:delete-{$modelKebab}')\n        ->name('force_destroy');"
            : '';

        $trashTabs = $def->softDeletes ? $this->renderTrashTabs($modelSnake) : '';
        $exportButton = $def->exportable ? $this->renderExportButton($modelSnake) : '';
        $rowActions = $this->renderRowActions($modelSnake, $modelKebab, $def->softDeletes);
        $mediaPickerModal = $def->hasMediaFields() ? "\n    <x-jangkeyte::forms.media-picker-modal />" : '';

        return [
            'MODULE_NAMESPACE' => $moduleNamespace,
            'MODULE_NAME' => $def->moduleName,
            'MODULE_KEY' => strtolower($def->moduleName),
            'MODEL_NAME' => $modelName,
            'MODEL_VAR' => $def->modelVar(),
            'MODEL_SNAKE' => $modelSnake,
            'MODEL_KEBAB' => $modelKebab,
            'TABLE_NAME' => $tableName,
            'HEADLINE' => $def->headline(),
            'HEADLINE_PLURAL' => $def->headlinePlural(),
            'MIGRATION_COLUMNS' => implode("\n                ", $migrations),
            'MODEL_IMPORTS' => implode("\n", $modelImports),
            'MODEL_TRAITS' => $modelTraits,
            'SORTABLE_COLUMNS' => implode(",\n        ", array_map(static fn (string $c): string => "'{$c}'", $def->sortableColumns())),
            'FILLABLE_COLUMNS' => implode(",\n        ", $fillables),
            'CAST_COLUMNS' => $casts === [] ? '//' : implode(",\n            ", $casts) . ',',
            'BELONGS_TO_RELATIONS' => $belongsToMethods === [] ? '' : "\n\n" . implode("\n\n", $belongsToMethods),
            'FILTER_SEARCH' => implode("\n            ", $filterConditions),
            'WITH_RELATIONS_ARRAY' => implode(', ', $withRelations),
            'FILE_FIELDS_ARRAY' => implode(', ', $fileFields),
            'BOOLEAN_FIELDS_ARRAY' => implode(', ', $booleanFields),
            'RELATION_OPTIONS_BODY' => $relationOptionsBody,
            'SERVICE_IMPORTS' => $serviceImports,
            'SOFT_DELETE_SERVICE_METHODS' => $softDeleteServiceMethods,
            'EXPORT_METHOD' => $exportMethod,
            'VALIDATION_RULES' => implode(",\n            ", $rules) . ',',
            'CONTROLLER_IMPORTS' => $controllerImports,
            'CONTROLLER_EXTRA_METHODS' => $controllerExtraMethods,
            'EXPORT_ROUTE' => $exportRoute,
            'SOFT_DELETE_ROUTES' => $softDeleteRoutes,
            'TRASH_TABS' => $trashTabs,
            'EXPORT_BUTTON' => $exportButton,
            'TABLE_HEADERS' => implode("\n                                ", $headers),
            'TABLE_CELLS' => implode("\n                                ", $cells),
            'ROW_ACTIONS' => $rowActions,
            'FORM_FIELDS' => implode("\n", $inputs),
            'MEDIA_PICKER_MODAL' => $mediaPickerModal,
        ];
    }

    protected function uploadedFileValidationRule(string $col, string $reqRuleExpression, string $mimesRuleExpression): string
    {
        return <<<PHP
'{$col}' => [
                {$reqRuleExpression},
                'file',
                {$mimesRuleExpression},
                'max:' . config('jangkeyte.media.max_size_kb', 5120),
                static function (string \$attribute, mixed \$value, \\Closure \$fail): void {
                    if (! \$value instanceof \\Illuminate\\Http\\UploadedFile) {
                        return;
                    }

                    try {
                        app(\\JangKeyte\\Services\\MediaService::class)->validateUploadedFile(\$value);
                    } catch (\\InvalidArgumentException \$e) {
                        \$fail(\$e->getMessage());
                    }
                },
            ]
PHP;
    }

    protected function mediaPathValidationRule(string $col): string
    {
        return <<<PHP
'{$col}_media_path' => [
                'nullable',
                'string',
                'max:500',
                static function (string \$attribute, mixed \$value, \\Closure \$fail): void {
                    if (! is_string(\$value) || trim(\$value) === '') {
                        return;
                    }

                    try {
                        app(\\JangKeyte\\Services\\MediaService::class)->resolveSafePath(\$value);
                    } catch (\\InvalidArgumentException \$e) {
                        \$fail(\$e->getMessage());
                    }
                },
            ]
PHP;
    }

    protected function renderSortableHeader(string $modelSnake, string $col, string $label): string
    {
        return <<<BLADE
<th>
                                    <a href="{{ route('{$modelSnake}.index', array_merge(request()->query(), ['sort' => '{$col}', 'direction' => (request('sort') === '{$col}' && request('direction', 'desc') === 'asc') ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>{{ __('{$label}') }}</span>
                                        <i class="bi {{ request('sort') === '{$col}' ? (request('direction', 'desc') === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up text-muted opacity-50' }} small"></i>
                                    </a>
                                </th>
BLADE;
    }

    protected function renderTableCell(FieldDefinition $field, ?string $relMethod, string $tableName): string
    {
        $col = $field->name;

        return match ($field->type) {
            'belongsTo' => "<td>{{ \$item->{$relMethod}?->" . ($field->relatedDisplayColumn ?? 'name') . " ?? '—' }}</td>",
            'image' => "<td>@if(\$item->{$col})<img src=\"{{ jangkeyte_media_url(\$item->{$col}, '{$tableName}') }}\" alt=\"\" style=\"height:48px;width:48px;object-fit:cover;\" class=\"rounded border\">@else<span class=\"text-muted\">—</span>@endif</td>",
            'file' => "<td>@if(\$item->{$col})<a href=\"{{ jangkeyte_media_url(\$item->{$col}, '{$tableName}') }}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"btn btn-outline-secondary btn-sm\"><i class=\"bi bi-file-earmark\"></i> {{ __('View File') }}</a>@else<span class=\"text-muted\">—</span>@endif</td>",
            'boolean' => "<td>@if(\$item->{$col})<span class=\"badge text-bg-success\">{{ __('Active') }}</span>@else<span class=\"badge text-bg-secondary\">{{ __('Inactive') }}</span>@endif</td>",
            'date' => "<td>{{ \$item->{$col}?->format('Y-m-d') ?? '—' }}</td>",
            'datetime' => "<td>{{ \$item->{$col}?->format('Y-m-d H:i') ?? '—' }}</td>",
            'richtext' => "<td>{{ \\Illuminate\\Support\\Str::limit(trim((string) preg_replace('/\\s+/u', ' ', html_entity_decode(strip_tags((string) \$item->{$col}), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 60) }}</td>",
            default => "<td>{{ \\Illuminate\\Support\\Str::limit((string) \$item->{$col}, 60) }}</td>",
        };
    }

    protected function renderExportExpression(FieldDefinition $field, ?string $relMethod): string
    {
        $col = $field->name;

        return match ($field->type) {
            'belongsTo' => "\$item->{$relMethod}?->" . ($field->relatedDisplayColumn ?? 'name'),
            'boolean' => "\$item->{$col} ? 'Yes' : 'No'",
            'date' => "\$item->{$col}?->format('Y-m-d')",
            'datetime' => "\$item->{$col}?->format('Y-m-d H:i:s')",
            'richtext' => "trim((string) preg_replace('/\\s+/u', ' ', html_entity_decode(strip_tags((string) \$item->{$col}), ENT_QUOTES | ENT_HTML5, 'UTF-8')))",
            default => "\$item->{$col}",
        };
    }

    protected function renderFormInput(FieldDefinition $field, string $label, string $tableName): string
    {
        $col = $field->name;
        $type = $field->type;

        if ($type === 'belongsTo') {
            return <<<BLADE
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label" for="{$col}">{{ __('{$label}') }}</label>
                            <div class="col-sm-10">
                                <select id="{$col}" name="{$col}" class="form-select @error('{$col}') is-invalid @enderror">
                                    <option value="">{{ __('-- Select --') }}</option>
                                    @foreach(\$relationOptions['{$col}'] ?? [] as \$optionId => \$optionLabel)
                                        <option value="{{ \$optionId }}" @selected((string) old('{$col}', \$item->{$col}) === (string) \$optionId)>{{ \$optionLabel }}</option>
                                    @endforeach
                                </select>
                                @error('{$col}')<div class="invalid-feedback">{{ \$message }}</div>@enderror
                            </div>
                        </div>
BLADE;
        }

        if ($type === 'boolean') {
            return <<<BLADE
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label" for="{$col}">{{ __('{$label}') }}</label>
                            <div class="col-sm-10 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="{$col}" name="{$col}" value="1" @checked(old('{$col}', \$item->exists ? \$item->{$col} : true))>
                                </div>
                            </div>
                        </div>
BLADE;
        }

        if ($type === 'richtext') {
            return <<<BLADE
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label" for="{$col}">{{ __('{$label}') }}</label>
                            <div class="col-sm-10">
                                <input type="hidden" id="{$col}" name="{$col}" value="{{ old('{$col}', \$item->{$col}) }}">
                                <div class="js-quill-editor bg-white border rounded @error('{$col}') border-danger @enderror" data-quill-editor data-quill-target="{$col}" style="min-height: 200px;"></div>
                                @error('{$col}')<div class="invalid-feedback d-block">{{ \$message }}</div>@enderror
                            </div>
                        </div>
BLADE;
        }

        if ($type === 'text') {
            return <<<BLADE
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label" for="{$col}">{{ __('{$label}') }}</label>
                            <div class="col-sm-10">
                                <textarea id="{$col}" name="{$col}" rows="4" class="form-control @error('{$col}') is-invalid @enderror">{{ old('{$col}', \$item->{$col}) }}</textarea>
                                @error('{$col}')<div class="invalid-feedback">{{ \$message }}</div>@enderror
                            </div>
                        </div>
BLADE;
        }

        if (in_array($type, ['image', 'file'], true)) {
            $acceptMode = $type === 'image' ? 'image' : 'all';
            $previewMarkup = $type === 'image'
                ? <<<BLADE
                                @php(\${$col}Url = old('{$col}_media_path') ? jangkeyte_media_url(old('{$col}_media_path'), '{$tableName}') : jangkeyte_media_url(\$item->{$col}, '{$tableName}'))
                                <div class="mb-2">
                                    <img id="{$col}_preview" src="{{ \${$col}Url ?: '' }}" alt="" style="height:80px;max-width:160px;object-fit:cover;" class="rounded border {{ \${$col}Url ? '' : 'd-none' }}">
                                </div>
BLADE
                : <<<BLADE
                                @php(\${$col}Url = old('{$col}_media_path') ? jangkeyte_media_url(old('{$col}_media_path'), '{$tableName}') : jangkeyte_media_url(\$item->{$col}, '{$tableName}'))
                                @if(\${$col}Url)
                                    <div class="mb-2">
                                        <a href="{{ \${$col}Url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-file-earmark"></i> {{ __('Current File') }}
                                        </a>
                                    </div>
                                @endif
BLADE;

            return <<<BLADE
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label" for="{$col}">{{ __('{$label}') }}</label>
                            <div class="col-sm-10">
{$previewMarkup}
                                <div class="input-group">
                                    <input type="file" id="{$col}" name="{$col}" class="form-control @error('{$col}') is-invalid @enderror" data-media-file-input data-media-path-input="{$col}_media_path" data-media-preview="{$col}_preview" data-media-label="{$col}_media_label">
                                    <button type="button" class="btn btn-outline-secondary" data-media-picker data-media-input="{$col}_media_path" data-media-preview="{$col}_preview" data-media-accept="{$acceptMode}">
                                        <i class="bi bi-images me-1"></i> {{ __('Choose from Media Library') }}
                                    </button>
                                </div>
                                <input type="hidden" id="{$col}_media_path" name="{$col}_media_path" value="{{ old('{$col}_media_path') }}">
                                <div class="form-text {{ old('{$col}_media_path') ? '' : 'd-none' }}" id="{$col}_media_label_wrap">
                                    {{ __('Selected Media') }}: <code id="{$col}_media_label">{{ old('{$col}_media_path') }}</code>
                                </div>
                                @error('{$col}')<div class="invalid-feedback d-block">{{ \$message }}</div>@enderror
                                @error('{$col}_media_path')<div class="invalid-feedback d-block">{{ \$message }}</div>@enderror
                            </div>
                        </div>
BLADE;
        }

        $htmlType = match ($type) {
            'integer', 'decimal' => 'number',
            'date' => 'date',
            'datetime' => 'datetime-local',
            'email' => 'email',
            default => 'text',
        };

        $stepAttr = $type === 'decimal' ? ' step="0.01"' : '';
        $valueExpr = match ($type) {
            'date' => "old('{$col}', \$item->{$col}?->format('Y-m-d'))",
            'datetime' => "old('{$col}', \$item->{$col}?->format('Y-m-d\\TH:i'))",
            default => "old('{$col}', \$item->{$col})",
        };

        return <<<BLADE
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label" for="{$col}">{{ __('{$label}') }}</label>
                            <div class="col-sm-10">
                                <input type="{$htmlType}"{$stepAttr} id="{$col}" name="{$col}" value="{{ {$valueExpr} }}" class="form-control @error('{$col}') is-invalid @enderror">
                                @error('{$col}')<div class="invalid-feedback">{{ \$message }}</div>@enderror
                            </div>
                        </div>
BLADE;
    }

    protected function renderSoftDeleteServiceMethods(): string
    {
        return <<<'PHP'


    public function restore(int|string $id): bool
    {
        return DB::transaction(fn (): bool => $this->repository->restore($id));
    }

    public function forceDelete(int|string $id): bool
    {
        return DB::transaction(fn (): bool => $this->repository->forceDelete($id));
    }
PHP;
    }

    /**
     * @param  array<int, string>  $exportHeaders
     * @param  array<int, string>  $exportCellExpressions
     */
    protected function renderExportServiceMethod(string $modelName, string $tableName, array $exportHeaders, array $exportCellExpressions): string
    {
        $headersCode = implode(', ', $exportHeaders);
        $cellsCode = implode(",\n                        ", $exportCellExpressions);

        return <<<PHP


    public function exportCsv(Request \$request): StreamedResponse
    {
        \$filter = \$request->filled('keyword') ? new {$modelName}Filter(\$request) : null;
        \$rawSort = \$request->query('sort');
        \$sortBy = is_string(\$rawSort) ? \$rawSort : null;
        \$rawDir = \$request->query('direction', 'desc');
        \$sortDir = is_string(\$rawDir) ? \$rawDir : 'desc';
        \$onlyTrashed = \$request->boolean('trashed');
        \$filename = '{$tableName}_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use (\$filter, \$sortBy, \$sortDir, \$onlyTrashed): void {
            \$handle = fopen('php://output', 'w');
            if (\$handle === false) {
                return;
            }

            fwrite(\$handle, "\\xEF\\xBB\\xBF");
            fputcsv(\$handle, [{$headersCode}]);

            \$this->repository->queryFiltered(\$filter, \$sortBy, \$sortDir, \$onlyTrashed, \$this->with)
                ->chunk(500, function (\$records) use (\$handle): void {
                    foreach (\$records as \$item) {
                        \$row = [
                        {$cellsCode},
                        ];
                        fputcsv(\$handle, array_map(fn (mixed \$cell): string => \$this->sanitizeCsvCell(\$cell), \$row));
                    }
                });

            fclose(\$handle);
        }, \$filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function sanitizeCsvCell(mixed \$value): string
    {
        if (\$value === null) {
            return '';
        }

        if (\$value instanceof \\DateTimeInterface) {
            return \$value->format('Y-m-d H:i:s');
        }

        if (is_bool(\$value)) {
            return \$value ? '1' : '0';
        }

        if (is_int(\$value) || is_float(\$value)) {
            return (string) \$value;
        }

        \$str = (string) \$value;
        if (! is_numeric(\$str) && \$str !== '' && in_array(\$str[0], ['=', '+', '-', '@', "\\t", "\\r", "\\n"], true)) {
            return "'" . \$str;
        }

        return \$str;
    }
PHP;
    }

    protected function renderControllerExtraMethods(string $modelSnake, bool $softDeletes, bool $exportable): string
    {
        $methods = [];

        $methods[] = <<<PHP


    public function bulkDestroy(Request \$request): RedirectResponse
    {
        \$validated = \$request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        \$count = \$this->service->bulkDelete(\$validated['ids']);

        return redirect()
            ->route('{$modelSnake}.index')
            ->with('success', __('Deleted :count record(s).', ['count' => \$count]));
    }
PHP;

        if ($exportable) {
            $methods[] = <<<'PHP'


    public function export(Request $request): StreamedResponse
    {
        return $this->service->exportCsv($request);
    }
PHP;
        }

        if ($softDeletes) {
            $methods[] = <<<PHP


    public function restore(int \$id): RedirectResponse
    {
        \$this->service->restore(\$id);

        return redirect()
            ->route('{$modelSnake}.index', ['trashed' => 1])
            ->with('success', __('Record restored successfully.'));
    }

    public function forceDestroy(int \$id): RedirectResponse
    {
        \$this->service->forceDelete(\$id);

        return redirect()
            ->route('{$modelSnake}.index', ['trashed' => 1])
            ->with('success', __('Record permanently deleted.'));
    }
PHP;
        }

        return implode('', $methods);
    }

    protected function renderTrashTabs(string $modelSnake): string
    {
        return <<<BLADE

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ !\$isTrashed ? 'active' : '' }}" href="{{ route('{$modelSnake}.index') }}">
                {{ __('Active') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ \$isTrashed ? 'active' : '' }}" href="{{ route('{$modelSnake}.index', ['trashed' => 1]) }}">
                <i class="bi bi-trash"></i> {{ __('Trash') }}
            </a>
        </li>
    </ul>
BLADE;
    }

    protected function renderExportButton(string $modelSnake): string
    {
        return <<<BLADE
<a href="{{ route('{$modelSnake}.export', request()->query()) }}" class="btn btn-outline-success btn-sm ms-2">
        <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('Export CSV') }}
    </a>
BLADE;
    }

    protected function renderRowActions(string $modelSnake, string $modelKebab, bool $softDeletes): string
    {
        $activeActions = <<<BLADE
@can('edit-{$modelKebab}')
                                        <a href="{{ route('{$modelSnake}.edit', \$item->id) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil-square"></i> {{ __('Edit') }}
                                        </a>
                                    @endcan
                                    @can('delete-{$modelKebab}')
                                        <form action="{{ route('{$modelSnake}.destroy', \$item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i> {{ __('Delete') }}
                                            </button>
                                        </form>
                                    @endcan
BLADE;

        if (! $softDeletes) {
            return $activeActions;
        }

        return <<<BLADE
@if(\$isTrashed)
                                        @can('edit-{$modelKebab}')
                                            <form action="{{ route('{$modelSnake}.restore', \$item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}
                                                </button>
                                            </form>
                                        @endcan
                                        @can('delete-{$modelKebab}')
                                            <form action="{{ route('{$modelSnake}.force_destroy', \$item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Permanently delete this record?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash-fill"></i> {{ __('Force Delete') }}
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        {$activeActions}
                                    @endif
BLADE;
    }

    protected function upsertMenuEntry(
        string $menuFile,
        string $modelSnake,
        string $headlinePlural,
        string $headline,
        string $icon,
        string $modelKebab,
    ): void {
        $items = File::isFile($menuFile) ? (require $menuFile) : [];
        if (! is_array($items)) {
            $items = [];
        }

        $items[$modelSnake] = [
            'label' => $headlinePlural,
            'icon' => $icon,
            'permission' => "browse-{$modelKebab}",
            'active_patterns' => ["admin/{$modelKebab}*"],
            'order' => 40,
            'children' => [
                [
                    'label' => "All {$headlinePlural}",
                    'route' => "{$modelSnake}.index",
                    'permission' => "browse-{$modelKebab}",
                    'active_patterns' => ["admin/{$modelKebab}"],
                ],
                [
                    'label' => "Add {$headline}",
                    'route' => "{$modelSnake}.create",
                    'permission' => "add-{$modelKebab}",
                    'active_patterns' => ["admin/{$modelKebab}/create"],
                ],
            ],
        ];

        $exported = $this->exportPhpArray($items);
        File::put($menuFile, "<?php\n\nreturn {$exported};\n");
    }

    /**
     * @param  array<mixed>  $array
     */
    protected function exportPhpArray(array $array, int $indent = 0): string
    {
        if ($array === []) {
            return '[]';
        }

        $pad = str_repeat('    ', $indent);
        $childPad = str_repeat('    ', $indent + 1);
        $isList = array_is_list($array);
        $lines = ['['];

        foreach ($array as $key => $value) {
            $prefix = $isList ? $childPad : $childPad . var_export((string) $key, true) . ' => ';
            if (is_array($value)) {
                $lines[] = $prefix . $this->exportPhpArray($value, $indent + 1) . ',';
            } else {
                $lines[] = $prefix . var_export($value, true) . ',';
            }
        }

        $lines[] = $pad . ']';

        return implode("\n", $lines);
    }

    protected function writeSchemaManifest(string $moduleDir, CrudDefinition $def): string
    {
        $modelSnake = $def->modelSnake();
        $modelKebab = $def->modelKebab();
        $schemasDir = "{$moduleDir}/Database/Schemas";
        File::ensureDirectoryExists($schemasDir);

        $schemaFile = "{$schemasDir}/{$modelSnake}.json";
        $payload = [
            'module' => $def->moduleName,
            'model' => $def->modelName,
            'model_snake' => $modelSnake,
            'model_kebab' => $modelKebab,
            'table' => $def->tableName(),
            'icon' => $def->icon,
            'route_index' => "{$modelSnake}.index",
            'url' => "/admin/{$modelKebab}",
            'soft_deletes' => $def->softDeletes,
            'exportable' => $def->exportable,
            'fields' => array_map(static fn (FieldDefinition $field): array => $field->toArray(), $def->fields),
            'generated_at' => now()->toIso8601String(),
        ];

        File::put(
            $schemaFile,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );

        return $schemaFile;
    }

    /**
     * @param  array<int, string>  $oldMigrationNames
     * @param  array<int, string>  $existingMigrations
     */
    protected function resetMigrationState(
        string $tableName,
        array $oldMigrationNames,
        array $existingMigrations,
        ?string $migrationFile,
    ): void {
        Schema::dropIfExists($tableName);

        if (Schema::hasTable('migrations')) {
            $toDelete = $oldMigrationNames;
            $pattern = '/^\d{4}_\d{2}_\d{2}_\d{6}_create_' . preg_quote($tableName, '/') . '_table$/';

            foreach (DB::table('migrations')->pluck('migration') as $migName) {
                if (is_string($migName) && preg_match($pattern, $migName) === 1) {
                    $toDelete[] = $migName;
                }
            }

            $toDelete = array_values(array_unique($toDelete));
            if ($toDelete !== []) {
                DB::table('migrations')->whereIn('migration', $toDelete)->delete();
            }
        }

        $pathsToInvalidate = $existingMigrations;
        if ($migrationFile !== null) {
            $pathsToInvalidate[] = $migrationFile;
        }
        $this->invalidateMigratorPathCache($pathsToInvalidate);
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
            // Safe fallback if Migrator internals change in future Laravel versions.
        }
    }

    protected function toAbsoluteForwardPath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $isAbsolute = str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1;

        if (! $isAbsolute) {
            $normalized = rtrim(str_replace('\\', '/', base_path()), '/') . '/' . ltrim($normalized, '/');
        }

        return $normalized;
    }
}
