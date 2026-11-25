<?php

namespace JangKeyte\Services\Generator;

use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class CrudDefinition
{
    public const STUDLY_REGEX = '/^[A-Z][A-Za-z0-9]*$/';

    public const ICON_REGEX = '/^[a-zA-Z0-9 _-]+$/';

    public string $moduleName;

    public string $modelName;

    public string $icon;

    public bool $softDeletes;

    public bool $exportable;

    public bool $runMigrations;

    public bool $force;

    /**
     * @var array<int, FieldDefinition>
     */
    public array $fields;

    /**
     * @param  array<int, FieldDefinition>  $fields
     */
    public function __construct(
        string $moduleName,
        string $modelName,
        string $icon = 'bi bi-collection',
        bool $softDeletes = false,
        bool $exportable = true,
        bool $runMigrations = true,
        bool $force = false,
        array $fields = [],
    ) {
        $cleanModule = trim($moduleName);
        if (preg_match(self::STUDLY_REGEX, $cleanModule) !== 1) {
            throw new InvalidArgumentException("Invalid module name [{$moduleName}]. Must match " . self::STUDLY_REGEX . '.');
        }

        $cleanModel = trim($modelName);
        if (preg_match(self::STUDLY_REGEX, $cleanModel) !== 1) {
            throw new InvalidArgumentException("Invalid model name [{$modelName}]. Must match " . self::STUDLY_REGEX . '.');
        }

        $cleanIcon = trim($icon) ?: 'bi bi-collection';
        if (preg_match(self::ICON_REGEX, $cleanIcon) !== 1) {
            throw new InvalidArgumentException("Invalid icon class [{$icon}].");
        }

        $reindexed = array_values($fields);
        if ($reindexed === []) {
            throw new InvalidArgumentException('At least one field definition is required.');
        }

        $seenNames = [];
        foreach ($reindexed as $field) {
            if (! $field instanceof FieldDefinition) {
                throw new InvalidArgumentException('All fields must be instances of FieldDefinition.');
            }

            if (isset($seenNames[$field->name])) {
                throw new InvalidArgumentException("Duplicate field name [{$field->name}] in CrudDefinition.");
            }

            $seenNames[$field->name] = true;
        }

        $this->moduleName = $cleanModule;
        $this->modelName = $cleanModel;
        $this->icon = $cleanIcon;
        $this->softDeletes = $softDeletes;
        $this->exportable = $exportable;
        $this->runMigrations = $runMigrations;
        $this->force = $force;
        $this->fields = $reindexed;
    }

    public function modelSnake(): string
    {
        return Str::snake($this->modelName);
    }

    public function modelKebab(): string
    {
        return Str::kebab($this->modelName);
    }

    public function modelVar(): string
    {
        return Str::camel($this->modelName);
    }

    public function tableName(): string
    {
        return Str::plural($this->modelSnake());
    }

    public function headline(): string
    {
        return Str::headline($this->modelName);
    }

    public function headlinePlural(): string
    {
        return Str::headline(Str::pluralStudly($this->modelName));
    }

    /**
     * @return array<int, FieldDefinition>
     */
    public function belongsToFields(): array
    {
        return array_values(array_filter(
            $this->fields,
            static fn (FieldDefinition $field): bool => $field->isBelongsTo()
        ));
    }

    public function hasBelongsTo(): bool
    {
        return $this->belongsToFields() !== [];
    }

    public function hasRichText(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->type === 'richtext') {
                return true;
            }
        }

        return false;
    }

    public function hasMediaFields(): bool
    {
        foreach ($this->fields as $field) {
            if (in_array($field->type, ['image', 'file'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function sortableColumns(): array
    {
        $columns = ['id'];

        foreach ($this->fields as $field) {
            if ($field->sortable) {
                $columns[] = $field->name;
            }
        }

        $columns[] = 'created_at';

        return array_values(array_unique($columns));
    }
}
