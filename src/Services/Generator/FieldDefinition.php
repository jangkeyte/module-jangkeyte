<?php

namespace JangKeyte\Services\Generator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class FieldDefinition
{
    public const IDENTIFIER_REGEX = '/^[a-z][a-z0-9_]*$/';

    public const FQCN_REGEX = '/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/';

    /**
     * @var array<int, string>
     */
    public const RESERVED_COLUMNS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var array<int, string>
     */
    public const ALLOWED_TYPES = [
        'string',
        'text',
        'richtext',
        'integer',
        'decimal',
        'boolean',
        'date',
        'datetime',
        'email',
        'image',
        'file',
        'belongsTo',
    ];

    /**
     * @var array<string, string>
     */
    public const TYPE_ALIASES = [
        'int' => 'integer',
        'float' => 'decimal',
        'bool' => 'boolean',
        'belongsto' => 'belongsTo',
    ];

    public string $name;

    public string $type;

    public bool $nullable;

    public bool $sortable;

    public ?string $relatedModel;

    public ?string $relatedDisplayColumn;

    public function __construct(
        string $name,
        string $type = 'string',
        bool $nullable = false,
        bool $sortable = true,
        ?string $relatedModel = null,
        ?string $relatedDisplayColumn = 'name',
    ) {
        $cleanName = trim($name);
        if (preg_match(self::IDENTIFIER_REGEX, $cleanName) !== 1) {
            throw new InvalidArgumentException("Invalid column name [{$name}]. Must match " . self::IDENTIFIER_REGEX . '.');
        }

        if (in_array($cleanName, self::RESERVED_COLUMNS, true)) {
            throw new InvalidArgumentException("Column name [{$cleanName}] is reserved.");
        }

        $rawType = trim($type);
        $normalizedType = self::TYPE_ALIASES[strtolower($rawType)] ?? $rawType;
        if (! in_array($normalizedType, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported field type [{$type}] for column [{$cleanName}].");
        }

        $cleanModel = null;
        $cleanDisplay = null;

        if ($normalizedType === 'belongsTo') {
            $candidateModel = ltrim(trim((string) $relatedModel), '\\');
            if ($candidateModel === '' || preg_match(self::FQCN_REGEX, $candidateModel) !== 1) {
                throw new InvalidArgumentException("BelongsTo field [{$cleanName}] requires a valid relatedModel FQCN.");
            }

            $candidateDisplay = trim((string) ($relatedDisplayColumn ?? 'name'));
            if ($candidateDisplay === '') {
                $candidateDisplay = 'name';
            }

            if (preg_match(self::IDENTIFIER_REGEX, $candidateDisplay) !== 1) {
                throw new InvalidArgumentException("Invalid relatedDisplayColumn [{$candidateDisplay}] for belongsTo field [{$cleanName}].");
            }

            $cleanModel = $candidateModel;
            $cleanDisplay = $candidateDisplay;
        }

        $this->name = $cleanName;
        $this->type = $normalizedType;
        $this->nullable = $nullable;
        $this->sortable = $sortable;
        $this->relatedModel = $cleanModel;
        $this->relatedDisplayColumn = $cleanDisplay;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $rawName = $data['name'] ?? '';
        if (! is_scalar($rawName)) {
            throw new InvalidArgumentException('Field [name] must be a string.');
        }

        $rawType = $data['type'] ?? 'string';
        if (! is_scalar($rawType)) {
            throw new InvalidArgumentException('Field [type] must be a string.');
        }

        $nullable = filter_var($data['nullable'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sortable = array_key_exists('sortable', $data)
            ? filter_var($data['sortable'], FILTER_VALIDATE_BOOLEAN)
            : true;

        $rawRelatedModel = $data['related_model'] ?? $data['relatedModel'] ?? null;
        if ($rawRelatedModel !== null && ! is_scalar($rawRelatedModel)) {
            throw new InvalidArgumentException('Field [related_model] must be a string.');
        }

        $rawRelatedDisplay = $data['related_display'] ?? $data['related_display_column'] ?? $data['relatedDisplayColumn'] ?? 'name';
        if (! is_scalar($rawRelatedDisplay)) {
            throw new InvalidArgumentException('Field [related_display] must be a string.');
        }

        return new self(
            name: (string) $rawName,
            type: (string) $rawType,
            nullable: $nullable,
            sortable: $sortable,
            relatedModel: $rawRelatedModel !== null ? (string) $rawRelatedModel : null,
            relatedDisplayColumn: (string) $rawRelatedDisplay,
        );
    }

    public static function fromCliSegment(string $segment): self
    {
        $normalized = str_replace('\\\\', '\\', trim($segment));
        $parts = explode(':', $normalized, 4);

        $rawCol = trim($parts[0] ?? '');
        $col = preg_match('/^[A-Za-z0-9_]+$/', $rawCol) === 1
            ? Str::snake($rawCol)
            : $rawCol;

        $rawType = trim($parts[1] ?? 'string') ?: 'string';
        $canonicalType = self::TYPE_ALIASES[strtolower($rawType)] ?? $rawType;

        $relatedModel = isset($parts[2]) && trim($parts[2]) !== '' ? trim($parts[2]) : null;
        $relatedDisplay = isset($parts[3]) && trim($parts[3]) !== '' ? trim($parts[3]) : 'name';

        $nullable = in_array($canonicalType, ['text', 'richtext', 'date', 'datetime', 'image', 'file', 'boolean'], true);
        $sortable = ! in_array($canonicalType, ['text', 'richtext', 'image', 'file'], true);

        return new self(
            name: $col,
            type: $canonicalType,
            nullable: $nullable,
            sortable: $sortable,
            relatedModel: $relatedModel,
            relatedDisplayColumn: $relatedDisplay,
        );
    }

    public function isBelongsTo(): bool
    {
        return $this->type === 'belongsTo';
    }

    public function relationMethodName(): ?string
    {
        if (! $this->isBelongsTo()) {
            return null;
        }

        if (str_ends_with($this->name, '_id') && strlen($this->name) > 3) {
            return Str::camel(substr($this->name, 0, -3));
        }

        return Str::camel($this->name) . 'Relation';
    }

    public function relatedTableName(): ?string
    {
        if (! $this->isBelongsTo() || $this->relatedModel === null) {
            return null;
        }

        if (class_exists($this->relatedModel) && is_subclass_of($this->relatedModel, Model::class)) {
            try {
                /** @var Model $instance */
                $instance = new ($this->relatedModel)();

                return $instance->getTable();
            } catch (\Throwable) {
                // Fall back to plural snake convention below.
            }
        }

        return Str::plural(Str::snake(class_basename($this->relatedModel)));
    }

    public function relatedKeyName(): string
    {
        if ($this->isBelongsTo() && $this->relatedModel !== null && class_exists($this->relatedModel) && is_subclass_of($this->relatedModel, Model::class)) {
            try {
                /** @var Model $instance */
                $instance = new ($this->relatedModel)();

                return $instance->getKeyName() ?: 'id';
            } catch (\Throwable) {
                return 'id';
            }
        }

        return 'id';
    }

    /**
     * @return array{name: string, type: string, nullable: bool, sortable: bool, related_model: ?string, related_display: ?string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'sortable' => $this->sortable,
            'related_model' => $this->relatedModel,
            'related_display' => $this->relatedDisplayColumn,
        ];
    }
}
