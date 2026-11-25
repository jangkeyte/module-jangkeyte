<?php

namespace JangKeyte\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use JangKeyte\Console\Commands\GenerateCrudCommand;
use JangKeyte\Console\Commands\InstallCommand;
use JangKeyte\Console\Commands\ModuleListCommand;
use JangKeyte\Contracts\DashboardHandlerInterface;
use JangKeyte\Contracts\ExternalAuthenticatorInterface;
use JangKeyte\Contracts\PostLoginRedirectorInterface;
use JangKeyte\Http\Middleware\AdminMiddleware;
use JangKeyte\Http\Middleware\AuthenticateMiddleware;
use JangKeyte\Http\Middleware\BuilderGuardMiddleware;
use JangKeyte\Http\Middleware\LocaleMiddleware;
use JangKeyte\Http\Middleware\PermissionMiddleware;
use JangKeyte\Http\Middleware\RoleMiddleware;
use JangKeyte\Repositories\Permission\PermissionRepository;
use JangKeyte\Repositories\Permission\PermissionRepositoryInterface;
use JangKeyte\Repositories\Role\RoleRepository;
use JangKeyte\Repositories\Role\RoleRepositoryInterface;
use JangKeyte\Repositories\User\UserRepository;
use JangKeyte\Repositories\User\UserRepositoryInterface;
use JangKeyte\Services\DefaultDashboardHandler;
use JangKeyte\Services\DefaultPostLoginRedirector;
use JangKeyte\Services\Generator\CrudGeneratorService;
use JangKeyte\Services\Generator\ModelDiscoveryService;
use JangKeyte\Services\MediaService;
use JangKeyte\Services\ModuleStudioService;
use JangKeyte\Services\NullExternalAuthenticator;
use JangKeyte\Services\PermissionRegistrar;
use JangKeyte\Services\SettingService;
use JangKeyte\Support\MenuRegistry;
use JangKeyte\Support\ModuleLoader;
use JangKeyte\Support\StubGenerator;

class JangKeyteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $packageRoot = dirname(__DIR__, 2);

        $this->mergeConfigFrom($packageRoot . '/config/jangkeyte.php', 'jangkeyte');

        // Core Singletons & Default Extensible Contracts
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(ModuleLoader::class);
        $this->app->singleton(StubGenerator::class);
        $this->app->singleton(PermissionRegistrar::class);
        $this->app->singleton(SettingService::class);
        $this->app->singleton(MediaService::class);
        $this->app->singleton(ModelDiscoveryService::class);
        $this->app->singleton(CrudGeneratorService::class);
        $this->app->singleton(ModuleStudioService::class);

        $this->app->bindIf(ExternalAuthenticatorInterface::class, NullExternalAuthenticator::class);
        $this->app->bindIf(PostLoginRedirectorInterface::class, DefaultPostLoginRedirector::class);
        $this->app->bindIf(DashboardHandlerInterface::class, DefaultDashboardHandler::class);

        // Core RBAC Repositories
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);

        // Register all enabled Domain Modules in modules/*
        $this->registerEnabledModules();
    }

    public function boot(Router $router, PermissionRegistrar $permissionRegistrar, MenuRegistry $menuRegistry): void
    {
        $packageRoot = dirname(__DIR__, 2);

        Paginator::useBootstrapFive();

        // Register Middleware Aliases safely without overriding Laravel's 'web' group
        $router->aliasMiddleware('auth', AuthenticateMiddleware::class);
        $router->aliasMiddleware('user', AuthenticateMiddleware::class);
        $router->aliasMiddleware('admin', AdminMiddleware::class);
        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('locale', LocaleMiddleware::class);
        $router->aliasMiddleware('jangkeyte.builder', BuilderGuardMiddleware::class);

        // Views & Blade Component Namespaces (JangKeyte primary + backward-compatible aliases)
        $viewsPath = $packageRoot . '/resources/views';
        $this->loadViewsFrom($viewsPath, 'JangKeyte');
        $this->loadViewsFrom($viewsPath, 'Authentication');
        $this->loadViewsFrom($viewsPath, 'Authetication');

        Blade::componentNamespace('JangKeyte\\View\\Components', 'jangkeyte');
        Blade::componentNamespace('JangKeyte\\View\\Components', 'authentication');
        Blade::componentNamespace('JangKeyte\\View\\Components', 'authetication');
        Blade::anonymousComponentPath($viewsPath . '/components', 'jangkeyte');

        // Blade RBAC Directives
        Blade::directive('role', fn ($role) => "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>");
        Blade::directive('endrole', fn () => '<?php endif; ?>');
        Blade::directive('permission', fn ($perm) => "<?php if(auth()->check() && auth()->user()->can({$perm})): ?>");
        Blade::directive('endpermission', fn () => '<?php endif; ?>');

        // Cached RBAC Gates
        $permissionRegistrar->registerGates();

        // Core Routes, Migrations & Translations
        $this->loadRoutesFrom($packageRoot . '/routes/web.php');
        $this->loadMigrationsFrom($packageRoot . '/database/migrations');
        $this->loadJsonTranslationsFrom($packageRoot . '/resources/lang');

        // Register Default Core Sidebar Menu Items
        $this->registerCoreMenus($menuRegistry);

        // Publishables
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $packageRoot . '/config/jangkeyte.php' => config_path('jangkeyte.php'),
            ], 'jangkeyte-config');

            $this->publishes([
                $packageRoot . '/resources/assets' => public_path('assets'),
            ], 'jangkeyte-assets');

            $this->commands([
                InstallCommand::class,
                GenerateCrudCommand::class,
                ModuleListCommand::class,
            ]);
        }
    }

    protected function registerEnabledModules(): void
    {
        /** @var ModuleLoader $loader */
        $loader = $this->app->make(ModuleLoader::class);

        foreach ($loader->enabledModules() as $module) {
            if (class_exists($module['provider'])) {
                $this->app->register($module['provider']);
            }
        }
    }

    protected function registerCoreMenus(MenuRegistry $menuRegistry): void
    {
        $menuRegistry->register('dashboard', [
            'label' => 'Dashboard',
            'icon' => 'bi bi-speedometer2',
            'route' => 'dashboard',
            'permission' => 'browse-dashboard',
            'active_patterns' => ['dashboard', 'admin/dashboard'],
            'order' => 1,
        ]);

        $menuRegistry->register('media_manager', [
            'label' => 'Media Manager',
            'icon' => 'bi bi-images',
            'route' => 'admin.media.index',
            'permission' => 'browse-media',
            'active_patterns' => ['admin/media*'],
            'order' => 80,
        ]);

        $menuRegistry->register('module_studio', [
            'label' => 'Module Studio',
            'icon' => 'bi bi-cpu',
            'route' => 'admin.modules.index',
            'permission' => 'browse-module',
            'active_patterns' => ['admin/modules*'],
            'order' => 85,
        ]);

        $menuRegistry->register('users_management', [
            'label' => 'Users',
            'icon' => 'bi bi-person-gear',
            'permission' => 'browse-user',
            'active_patterns' => ['admin/user*', 'admin/role*'],
            'order' => 90,
            'children' => [
                [
                    'label' => 'All Users',
                    'route' => 'admin_user',
                    'permission' => 'browse-user',
                    'active_patterns' => ['admin/user*'],
                ],
                [
                    'label' => 'Roles',
                    'route' => 'admin_role',
                    'permission' => 'browse-role',
                    'active_patterns' => ['admin/role*'],
                ],
            ],
        ]);

        $menuRegistry->register('settings', [
            'label' => 'Settings',
            'icon' => 'bi bi-gear',
            'route' => 'admin.settings.index',
            'permission' => 'browse-setting',
            'active_patterns' => ['admin/settings*'],
            'order' => 95,
        ]);
    }
}
