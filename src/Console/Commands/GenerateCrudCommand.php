<?php

namespace JangKeyte\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JangKeyte\Services\Generator\CrudDefinition;
use JangKeyte\Services\Generator\CrudGeneratorService;
use JangKeyte\Services\Generator\FieldDefinition;

class GenerateCrudCommand extends Command
{
    protected $signature = 'jangkeyte:crud
                            {model : Model name in StudlyCase, e.g. Trip or Vehicle}
                            {--module= : Target domain module name, e.g. TripBooking or Fleet}
                            {--fields= : Comma-separated columns, e.g. title:string,author_id:belongsTo:JangKeyte\\Models\\User:name,body:richtext}
                            {--icon=bi bi-collection : Bootstrap icon class for sidebar menu}
                            {--soft-deletes : Enable SoftDeletes, Trash view, Restore and Force Delete}
                            {--export : Enable CSV export endpoint and button}
                            {--no-export : Disable CSV export endpoint and button}
                            {--no-migrate : Do not run migration and permission seeding automatically}
                            {--force : Overwrite existing generated files}';

    protected $aliases = ['crud:generate'];

    protected $description = 'Generate a complete Best-Practice CRUD (Module, Migration, Model, Filter, Repository, Service, Request, Controller, Routes, Views, Menu & RBAC Permissions) in 1 command';

    public function handle(CrudGeneratorService $generator): int
    {
        $rawModel = trim((string) $this->argument('model'));
        $modelName = Str::studly($rawModel);

        $rawModule = $this->option('module');
        if (! $rawModule && $this->input->isInteractive()) {
            $rawModule = $this->ask('Target Module name', $modelName);
        }
        $moduleName = Str::studly($rawModule ?: $modelName);

        $rawFields = $this->option('fields');
        if (! $rawFields && $this->input->isInteractive()) {
            $rawFields = $this->ask(
                'Columns (e.g. name:string,description:text,start_date:date,image:file,is_active:boolean)',
                'name:string,description:text,is_active:boolean'
            );
        }

        $fieldsString = trim((string) ($rawFields ?: 'name:string,description:text,is_active:boolean'));

        try {
            $fields = [];
            foreach (explode(',', $fieldsString) as $segment) {
                $segment = trim($segment);
                if ($segment === '') {
                    continue;
                }
                $fields[] = FieldDefinition::fromCliSegment($segment);
            }

            if ($fields === []) {
                $fields[] = new FieldDefinition('name', 'string');
            }

            $definition = new CrudDefinition(
                moduleName: $moduleName,
                modelName: $modelName,
                icon: (string) $this->option('icon'),
                softDeletes: (bool) $this->option('soft-deletes'),
                exportable: ! (bool) $this->option('no-export'),
                runMigrations: ! (bool) $this->option('no-migrate'),
                force: (bool) $this->option('force'),
                fields: $fields,
            );

            $this->components->info("Scaffolding CRUD [{$definition->modelName}] inside Module [{$definition->moduleName}]...");

            $result = $generator->generate($definition);
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($result['files'] as $label => $path) {
            $this->components->twoColumnDetail($label, $this->rel($path));
        }

        if ($definition->runMigrations) {
            $this->components->twoColumnDetail(
                'Database & RBAC',
                "Migrated [{$result['table']}] & seeded [*-{$definition->modelKebab()}] permissions"
            );
        }

        $this->newLine();
        $this->components->info("CRUD [{$definition->modelName}] is ready at URL: {$result['url']}");

        return self::SUCCESS;
    }

    protected function rel(string $path): string
    {
        $normalizedBase = rtrim(str_replace('\\', '/', base_path()), '/');
        $normalizedPath = str_replace('\\', '/', $path);

        return ltrim(str_replace($normalizedBase, '', $normalizedPath), '/');
    }
}
