<?php

namespace JangKeyte\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName = '';

    protected string $modulePath = '';

    /**
     * Optional explicit repository bindings: [Interface::class => Concrete::class]
     *
     * @var array<class-string, class-string>
     */
    protected array $repositories = [];

    /**
     * Route middleware applied automatically to module web routes.
     *
     * @var array<int, string>
     */
    protected array $routeMiddleware = ['web', 'locale'];

    public function register(): void
    {
        $this->registerModuleConfig();
        $this->registerModuleRepositories();
    }

    public function boot(): void
    {
        $this->bootModuleRoutes();
        $this->bootModuleViews();
        $this->bootModuleMigrations();
        $this->bootModuleTranslations();
        $this->bootModuleMenu();
    }

    protected function normalizedPath(string $subPath = ''): string
    {
        $base = rtrim(str_replace('\\', '/', $this->modulePath), '/');

        return $subPath === '' ? $base : $base . '/' . ltrim($subPath, '/');
    }

    protected function moduleKey(): string
    {
        return strtolower($this->moduleName);
    }

    protected function moduleNamespace(): string
    {
        $root = trim(config('jangkeyte.modules_namespace', 'Modules'), '\\');

        return "{$root}\\{$this->moduleName}";
    }

    protected function registerModuleConfig(): void
    {
        $configFile = $this->normalizedPath('Config/config.php');
        if (File::isFile($configFile)) {
            $this->mergeConfigFrom($configFile, $this->moduleKey());
            $this->publishes([
                $configFile => config_path($this->moduleKey() . '.php'),
            ], $this->moduleKey() . '-config');
        }
    }

    protected function registerModuleRepositories(): void
    {
        foreach ($this->repositories as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }

        $modelsDir = $this->normalizedPath('Models');
        if (! File::isDirectory($modelsDir)) {
            return;
        }

        $ns = $this->moduleNamespace();
        foreach (File::files($modelsDir) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $modelName = $file->getFilenameWithoutExtension();
            $interface = "{$ns}\\Repositories\\{$modelName}\\{$modelName}RepositoryInterface";
            $concrete = "{$ns}\\Repositories\\{$modelName}\\{$modelName}Repository";

            if (! isset($this->repositories[$interface]) && interface_exists($interface) && class_exists($concrete)) {
                $this->app->bind($interface, $concrete);
            }
        }
    }

    protected function bootModuleRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $routesDir = $this->normalizedPath('Routes');
        if (! File::isDirectory($routesDir)) {
            return;
        }

        $files = File::files($routesDir);
        usort($files, static fn ($a, $b) => strcmp($a->getFilename(), $b->getFilename()));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            Route::middleware($this->routeMiddleware)->group($file->getPathname());
        }
    }

    protected function bootModuleViews(): void
    {
        $viewsDir = $this->normalizedPath('Resources/views');
        if (File::isDirectory($viewsDir)) {
            $this->loadViewsFrom($viewsDir, $this->moduleName);
            $this->publishes([
                $viewsDir => resource_path('views/vendor/' . $this->moduleKey()),
            ], $this->moduleKey() . '-views');
        }
    }

    protected function bootModuleMigrations(): void
    {
        $migrationsDir = $this->normalizedPath('Database/Migrations');
        if (File::isDirectory($migrationsDir)) {
            $this->loadMigrationsFrom($migrationsDir);
        }
    }

    protected function bootModuleTranslations(): void
    {
        $langDir = $this->normalizedPath('Resources/lang');
        if (File::isDirectory($langDir)) {
            $this->loadTranslationsFrom($langDir, $this->moduleName);
            $this->loadJsonTranslationsFrom($langDir);
        }
    }

    protected function bootModuleMenu(): void
    {
        $menuFile = $this->normalizedPath('Config/menu.php');
        if (File::isFile($menuFile)) {
            $items = require $menuFile;
            if (is_array($items)) {
                $this->app->make(MenuRegistry::class)->registerMany($items);
            }
        }
    }
}
