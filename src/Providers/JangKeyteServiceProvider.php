<?php

namespace Modules\JangKeyte\src\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider\RouteServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class JangKeyteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    private $MODULE_NAME = 'JangKeyte';
    private $module_path = __DIR__ . '/../../';
    protected $seeds_path = 'Modules\JangKeyte\Database\Seeders';
    
    public function register() 
    { 
        // Khai báo middleware
        $this->load_middleware($this->module_path, $this->MODULE_NAME);

        // Khai báo config
        $this->load_config($this->module_path, $this->MODULE_NAME);

        // Bind repository cho module $MODULE_NAME
        $this->load_repository();
    }

    public function boot()
    {
        Paginator::useBootstrap();

        // Khai báo blade
        Blade::componentNamespace('Modules\\' . $this->MODULE_NAME . '\\src\\View\\Components', strtolower($this->MODULE_NAME));
        
        // Khai báo routes
        $this->load_route($this->module_path);

        // Khai báo views
        $this->load_view($this->module_path, $this->MODULE_NAME);
    
        // Khai báo migration
        $this->load_migration($this->module_path);

        // Khai báo languages
        $this->load_language($this->module_path, $this->MODULE_NAME);
    
        // Khai báo helpers
        $this->load_helper($this->module_path);

        // Khai báo commands
        $this->load_command($this->MODULE_NAME);

        // Khai báo observer
        $this->load_observer();
        
        // Khai báo seeders
        $this->load_seeder();
    }
    
    public function load_config($path, $module) {
        
        if (File::exists( $path . 'config' )) {
            //to published this config: php artisan vendor:publish --provider="Modules\JangKeyte\src\Providers\JangKeyteServiceProvider" --tag=config --force
            $config_dir = File::allFiles( $path . 'config' );            
            foreach ( $config_dir as $value ) {
                $this->publishes([
                    $value->getPathName() => config_path($value->getFileName()),
                ], 'config');
                $this->mergeConfigFrom(
                    $value->getPathName(), str_replace($value->getExtension(), '', $value->getFileName())
                );
            }
        } 
    }

    public function load_repository() {
        if (File::exists( $this->module_path . 'src\Models' )) {
            $model_dir = File::allFiles( $this->module_path . 'src\Models' ); 
            foreach ( $model_dir as $value ) {
                $model_name = str_replace('.' . $value->getExtension(), '', $value->getFileName());
                $this->app->bind(
                    'Modules\\' . $this->MODULE_NAME . '\\src\\Repositories\\' . $model_name . '\\' . $model_name . 'RepositoryInterface',
                    'Modules\\' . $this->MODULE_NAME . '\\src\\Repositories\\' . $model_name . '\\' . $model_name . 'Repository'
                );
            }
        } 
    }

    public function load_middleware($path, $module) {
        $middleare = [
            $module =>   __DIR__ . '\\' . $module . '\\src\\Http\\Controllers\Middlewares\\' . $module . 'Middleware',
        ];
        foreach ($middleare as $key => $value) {
            $this->app['router']->pushMiddlewareToGroup($key, $value);
        }           
    }

    public function load_route($path) {     
        if (File::exists( $path . 'routes' )) {
            // Tất cả files có tại thư mục routes
            $route_dir = File::allFiles( $path . 'routes' );
            foreach ( $route_dir as $key => $value ) {
                $file = $value->getPathName();                
                $this->loadRoutesFrom( $file );
            }            
        }        
    }

    public function load_view($path, $module) {        
        if (File::exists( $path . 'resources/views')) {
            // Để gọi view thì ta sử dụng namespace ở phía trước, ví dụ module Demo: view('Demo::index'), @extends('Demo::index'), @include('Demo::index')
            $this->loadViewsFrom( $path . 'resources/views', $module );
        }
        $this->publishes([
            $path . 'resources/views' => resource_path("views"),
            $path . 'resources/assets' => public_path("assets")
        ]);
    }

    public function load_migration($path) {        
        if (File::exists( $path . 'database/migrations' )) {
            // Toàn bộ file migration của modules sẽ tự động được load
            $this->loadMigrationsFrom( $path . 'database/migrations');
        }
    }

    public function load_language($path, $module) {        
        if (File::exists( $path . 'resources/lang' )) {
            // Đa ngôn ngữ theo file php
            // Dùng đa ngôn ngữ tại file php resources/lang/en/general.php : @lang('Demo::general.hello')
            $this->loadTranslationsFrom( $path . 'resources/lang', $module );
            // Đa ngôn ngữ theo file json
            $this->loadJSONTranslationsFrom( $path . 'resources/lang');
        }
    }

    public function load_helper($path) {        
        if (File::exists( $path . 'helpers' )) {
            // khai báo helpers
            foreach (glob($path . 'helpers/*.php') as $file) {
                require_once($file);
            }
            
            /*
            // Tất cả files có tại thư mục helpers
            $helper_dir = File::allFiles( $path . 'helpers' );
            foreach ( $helper_dir as $key => $value ) {
                $file = $value->getPathName();
                require $file;
            }
            */
        }
    }

    public function load_command($module) {        
        // Khai báo commands
        $this->commands([
            // namespace của commands đặt tại đây
            '\\Modules\\' . $module . '\\src\\Http\\Commands\\' . $module . 'Command'
        ]);
    }

    public function load_observer() {        
        //Customer::observe(CustomerObserver::class);
    }
    
    public function load_seeder() {
        if ($this->app->runningInConsole()) {
            if ($this->isConsoleCommandContains([ 'db:seed', '--seed' ], [ '--class', 'help', '-h' ])) {
                $this->addSeedsAfterConsoleCommandFinished();
            }
        }
    }
    
    /**
     * Get a value that indicates whether the current command in console
     * contains a string in the specified $fields.
     *
     * @param string|array $contain_options
     * @param string|array $exclude_options
     *
     * @return bool
     */
    protected function isConsoleCommandContains($contain_options, $exclude_options = null) : bool
    {
        $args = Request::server('argv', null);
        if (is_array($args)) {
            $command = implode(' ', $args);
            if (
                Str::contains($command, $contain_options) &&
                ($exclude_options == null || !Str::contains($command, $exclude_options))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add seeds from the $seed_path after the current command in console finished.
     */
    protected function addSeedsAfterConsoleCommandFinished()
    {
        Event::listen(CommandFinished::class, function(CommandFinished $event) {
            // Accept command in console only,
            // exclude all commands from Artisan::call() method.
            if ($event->output instanceof ConsoleOutput) {
                addSeedsFrom($this->seeds_path);
            }
        });
    }
}