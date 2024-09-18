<?php

namespace Modules\Authetication\src\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider\RouteServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Modules\Authetication\src\Models\Banner;
use Modules\Authetication\src\Models\Setting;

use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AutheticationServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    private $MODULE_NAME = 'Authetication';
    private $module_path = __DIR__ . '/../../';
    protected $seeds_path = 'Modules\Authetication\Database\Seeders';
    
    public function register() 
    { 
        // Khai báo middleware
        $this->load_middleware($this->module_path, $this->MODULE_NAME);

        // Khai báo config
        $this->load_config($this->module_path, $this->MODULE_NAME);

        // Bind repository cho module $MODULE_NAME
        $data = array('User', 'Role', 'Permission');
        $this->load_repository($data, $this->MODULE_NAME);
    }

    public function boot()
    {        
        // Khai báo blade
        Blade::componentNamespace('Modules\\' . $this->MODULE_NAME . '\\src\\View\\Components', strtolower($this->MODULE_NAME));
           
        Paginator::useBootstrap();

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

        // Khai báo observer
        $this->load_observer();
        
        // Khai báo seeders
        $this->load_seeder();
    }
    
    public function load_config($path, $module) {
        
        if (File::exists( $path . 'config' )) {
            //to published this config: php artisan vendor:publish --provider="Modules\Authetication\src\Providers\AutheticationServiceProvider" --tag=config --force
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

    public function load_repository($data, $module) {        
        foreach ($data as $item) {
            $this->app->bind(
                'Modules\\' . $module . '\\src\\Repositories\\' . $item . '\\' . $item . 'RepositoryInterface',
                'Modules\\' . $module . '\\src\\Repositories\\' . $item . '\\' . $item . 'Repository'
            );
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
            '\\modules\\' . $module . '\\src\\Http\\Commands\\' . $module . 'Command'
        ]);
    }

    public function load_observer() {        
        
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
                $this->addSeedsFrom($this->seeds_path);
            }
        });
    }

    /**
     * Register seeds.
     *
     * @param string  $seeds_path
     * @return void
     */
    protected function addSeedsFrom($seeds_path)
    {
        $file_names = glob( $seeds_path . '/*.php');
        //dd($seeds_path, $file_names);
        foreach ($file_names as $filename)
        {
            $classes = $this->getClassesFromFile($filename);
            foreach ($classes as $class) {
                if(str_contains($class, 'DatabaseSeeder')) {
                    echo "\033[1;33mSeeding:\033[0m {$class}\n";
                    $startTime = microtime(true);
                    Artisan::call('db:seed', [ '--class' => $class, '--force' => '' ]);
                    $runTime = round(microtime(true) - $startTime, 2);
                    echo "\033[0;32mSeeded:\033[0m {$class} ({$runTime} seconds)\n";
                }
            }
        }
    }

    /**
     * Get full class names declared in the specified file.
     *
     * @param string $filename
     * @return array an array of class names.
     */
    private function getClassesFromFile(string $filename) : array
    {
        // Get namespace of class (if vary)
        $namespace = "";
        $lines = file($filename);
        $namespaceLines = preg_grep('/^namespace /', $lines);
        if (is_array($namespaceLines)) {
            $namespaceLine = array_shift($namespaceLines);
            $match = array();
            preg_match('/^namespace (.*);\v+$/', $namespaceLine, $match);
            $namespace = array_pop($match);
        }

        // Get name of all class has in the file.
        $classes = array();
        $php_code = file_get_contents($filename);
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                $class_name = $tokens[$i][1];
                if ($namespace !== "") {
                    $classes[] = $namespace . "\\$class_name";
                } else {
                    $classes[] = $class_name;
                }
            }
        }

        return $classes;
    }
}