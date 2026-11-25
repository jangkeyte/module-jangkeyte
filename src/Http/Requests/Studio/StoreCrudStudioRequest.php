<?php

namespace JangKeyte\Http\Requests\Studio;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use JangKeyte\Http\Requests\BaseFormRequest;
use JangKeyte\Services\Generator\CrudDefinition;
use JangKeyte\Services\Generator\FieldDefinition;
use ReflectionClass;

class StoreCrudStudioRequest extends BaseFormRequest
{
    /**
     * @var array<int, string>
     */
    public const PHP_KEYWORDS = [
        'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch',
        'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do',
        'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach',
        'endif', 'endswitch', 'endwhile', 'enum', 'eval', 'exit', 'extends',
        'final', 'finally', 'fn', 'for', 'foreach', 'function', 'global', 'goto',
        'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof',
        'interface', 'isset', 'list', 'match', 'namespace', 'new', 'or', 'print',
        'private', 'protected', 'public', 'readonly', 'require', 'require_once',
        'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use',
        'var', 'while', 'xor', 'yield', 'self', 'parent', 'bool', 'int', 'float',
        'string', 'iterable', 'object', 'mixed', 'never', 'void', 'null', 'true',
        'false', '__halt_compiler',
    ];

    /**
     * @var array<int, string>
     */
    public const CORE_MODULE_COLLISIONS = [
        'jangkeyte', 'core', 'module', 'modules', 'app', 'auth', 'cache', 'config',
        'database', 'filesystems', 'logging', 'mail', 'queue', 'services',
        'session', 'view',
    ];

    /**
     * @var array<int, string>
     */
    public const CORE_MODEL_COLLISIONS = [
        'user', 'role', 'permission', 'setting', 'media', 'module', 'dashboard',
    ];

    public function authorize(): bool
    {
        return (bool) (
            function_exists('jangkeyte_builder_enabled')
            && jangkeyte_builder_enabled()
            && $this->user()
            && method_exists($this->user(), 'isSuperAdmin')
            && $this->user()->isSuperAdmin()
        );
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('module_name') && is_string($this->input('module_name'))) {
            $merged['module_name'] = trim($this->input('module_name'));
        }

        if ($this->has('model_name') && is_string($this->input('model_name'))) {
            $merged['model_name'] = trim($this->input('model_name'));
        }

        if ($this->has('icon')) {
            $rawIcon = $this->input('icon');
            if (is_string($rawIcon)) {
                $trimmedIcon = trim($rawIcon);
                $merged['icon'] = $trimmedIcon !== '' ? $trimmedIcon : 'bi bi-collection';
            } elseif ($rawIcon === null) {
                $merged['icon'] = 'bi bi-collection';
            }
        } else {
            $merged['icon'] = 'bi bi-collection';
        }

        $boolDefaults = [
            'soft_deletes' => false,
            'exportable' => true,
            'run_migrations' => true,
            'force' => false,
        ];

        foreach ($boolDefaults as $key => $default) {
            if ($this->exists($key)) {
                $merged[$key] = $this->normalizeBooleanInput($this->input($key));
            } else {
                $merged[$key] = $default;
            }
        }

        $rawFields = $this->input('fields');
        if (is_array($rawFields)) {
            $normalizedFields = [];
            foreach (array_values($rawFields) as $index => $row) {
                if (! is_array($row)) {
                    $normalizedFields[$index] = $row;
                    continue;
                }

                if (array_key_exists('name', $row) && is_string($row['name'])) {
                    $row['name'] = trim($row['name']);
                }

                if (array_key_exists('type', $row) && is_string($row['type'])) {
                    $row['type'] = trim($row['type']);
                }

                $row['nullable'] = array_key_exists('nullable', $row)
                    ? $this->normalizeBooleanInput($row['nullable'])
                    : false;

                $row['sortable'] = array_key_exists('sortable', $row)
                    ? $this->normalizeBooleanInput($row['sortable'])
                    : true;

                if (array_key_exists('related_model', $row) && is_string($row['related_model'])) {
                    $row['related_model'] = ltrim(trim($row['related_model']), '\\');
                }

                if (array_key_exists('related_display', $row)) {
                    if (is_string($row['related_display'])) {
                        $trimmedDisplay = trim($row['related_display']);
                        $row['related_display'] = $trimmedDisplay !== '' ? $trimmedDisplay : 'name';
                    } elseif ($row['related_display'] === null) {
                        $row['related_display'] = 'name';
                    }
                }

                $normalizedFields[$index] = $row;
            }

            $merged['fields'] = $normalizedFields;
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'module_name' => [
                'bail',
                'required',
                'string',
                'max:64',
                'regex:' . CrudDefinition::STUDLY_REGEX,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $lower = strtolower($value);
                    if (in_array($lower, self::PHP_KEYWORDS, true)) {
                        $fail(__('The :attribute cannot be a reserved PHP keyword.'));

                        return;
                    }
                    if (in_array($lower, self::CORE_MODULE_COLLISIONS, true)) {
                        $fail(__('The :attribute conflicts with a core package or framework namespace.'));
                    }
                },
            ],
            'model_name' => [
                'bail',
                'required',
                'string',
                'max:64',
                'regex:' . CrudDefinition::STUDLY_REGEX,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $lower = strtolower($value);
                    if (in_array($lower, self::PHP_KEYWORDS, true)) {
                        $fail(__('The :attribute cannot be a reserved PHP keyword.'));

                        return;
                    }
                    if (in_array($lower, self::CORE_MODEL_COLLISIONS, true)) {
                        $fail(__('The :attribute conflicts with a core system resource.'));
                    }
                },
            ],
            'icon' => ['bail', 'nullable', 'string', 'max:100', 'regex:' . CrudDefinition::ICON_REGEX],
            'soft_deletes' => ['sometimes', 'boolean'],
            'exportable' => ['sometimes', 'boolean'],
            'run_migrations' => ['sometimes', 'boolean'],
            'force' => ['sometimes', 'boolean'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => [
                'bail',
                'required',
                'string',
                'max:64',
                'distinct',
                'regex:' . FieldDefinition::IDENTIFIER_REGEX,
                Rule::notIn(FieldDefinition::RESERVED_COLUMNS),
            ],
            'fields.*.type' => ['bail', 'required', 'string', Rule::in(FieldDefinition::ALLOWED_TYPES)],
            'fields.*.nullable' => ['sometimes', 'boolean'],
            'fields.*.sortable' => ['sometimes', 'boolean'],
            'fields.*.related_model' => [
                'bail',
                'nullable',
                'required_if:fields.*.type,belongsTo',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    if (preg_match('/^fields\.(.+)\.related_model$/', $attribute, $matches) !== 1) {
                        return;
                    }

                    $fields = $this->input('fields');
                    $rowKey = $matches[1];
                    $rowType = is_array($fields) && array_key_exists($rowKey, $fields) && is_array($fields[$rowKey])
                        ? ($fields[$rowKey]['type'] ?? null)
                        : Arr::get($fields, "{$rowKey}.type");

                    if ($rowType !== 'belongsTo') {
                        return;
                    }

                    $fqcn = ltrim(trim($value), '\\');
                    if ($fqcn === '' || preg_match(FieldDefinition::FQCN_REGEX, $fqcn) !== 1) {
                        $fail(__('The target model must be a valid fully-qualified class name.'));

                        return;
                    }

                    if (! class_exists($fqcn) || ! is_subclass_of($fqcn, Model::class)) {
                        $fail(__('The target model [:class] must be an existing Eloquent Model.', ['class' => $fqcn]));

                        return;
                    }

                    $reflection = new ReflectionClass($fqcn);
                    if ($reflection->isAbstract()) {
                        $fail(__('The target model [:class] cannot be abstract.', ['class' => $fqcn]));
                    }
                },
            ],
            'fields.*.related_display' => ['bail', 'nullable', 'string', 'max:64', 'regex:' . FieldDefinition::IDENTIFIER_REGEX],
        ];
    }

    public function toCrudDefinition(): CrudDefinition
    {
        $validated = $this->validated();

        $fields = array_map(
            static fn (array $row): FieldDefinition => FieldDefinition::fromArray($row),
            array_values($validated['fields'])
        );

        return new CrudDefinition(
            moduleName: (string) $validated['module_name'],
            modelName: (string) $validated['model_name'],
            icon: (string) ($validated['icon'] ?? 'bi bi-collection'),
            softDeletes: (bool) ($validated['soft_deletes'] ?? false),
            exportable: (bool) ($validated['exportable'] ?? true),
            runMigrations: (bool) ($validated['run_migrations'] ?? true),
            force: (bool) ($validated['force'] ?? false),
            fields: $fields,
        );
    }

    protected function normalizeBooleanInput(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && ($value === 0 || $value === 1)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $parsed = filter_var(trim($value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return $value;
    }
}
