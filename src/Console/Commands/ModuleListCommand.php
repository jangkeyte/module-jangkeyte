<?php

namespace JangKeyte\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JangKeyte\Support\ModuleLoader;

class ModuleListCommand extends Command
{
    protected $signature = 'jangkeyte:modules
                            {--enable= : Enable a module in modules_statuses.json}
                            {--disable= : Disable a module in modules_statuses.json}
                            {--remove= : Remove a module directory and its status entry}';

    protected $description = 'List all discovered modules or enable/disable/remove a module';

    public function handle(ModuleLoader $loader): int
    {
        if ($enable = $this->option('enable')) {
            $loader->setStatus($enable, true);
            $this->components->info("Module [{$enable}] has been enabled.");
        }

        if ($disable = $this->option('disable')) {
            $loader->setStatus($disable, false);
            $this->components->warn("Module [{$disable}] has been disabled.");
        }

        if ($remove = $this->option('remove')) {
            $modDir = $loader->modulesPath() . '/' . $remove;
            if (File::isDirectory($modDir)) {
                File::deleteDirectory($modDir);
            }
            $loader->removeStatus($remove);
            $this->components->info("Module [{$remove}] has been removed.");
        }

        $rows = [];
        foreach ($loader->allModules() as $mod) {
            $rows[] = [
                $mod['name'],
                $mod['enabled'] ? '<fg=green>Enabled</>' : '<fg=yellow>Disabled</>',
                $mod['provider'],
                $mod['path'],
            ];
        }

        if (empty($rows)) {
            $this->components->info('No domain modules installed in modules/. Generate one with: php artisan jangkeyte:crud Trip --module=TripBooking');

            return self::SUCCESS;
        }

        $this->table(['Module', 'Status', 'Provider', 'Path'], $rows);

        return self::SUCCESS;
    }
}
