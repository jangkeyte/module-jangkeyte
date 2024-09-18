<?php

namespace Modules\Authetication\src\Providers;

use Modules\Authetication\src\Models\Permission;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        try {
            Permission::get()->map(function ($permission) {
                Gate::define($permission->slug, function ($user) use ($permission) {
                    return $user->hasPermissionTo($permission);
                });
            });
        } catch (\Exception $e) {
            report($e);
            return false;
        }

        //Blade directives
        Blade::directive('role', function ($role) {
             return "if(auth()->check() && auth()->user()->hasRole({$role})) :"; //return this if statement inside php tag
        });

        Blade::directive('endrole', function ($role) {
             return "endif;"; //return this endif statement inside php tag
        });

        //Blade directives
        Blade::directive('permission', function ($permission) {
             return "if(auth()->check() && auth()->user()->can({$permission})) :"; //return this if statement inside php tag
        });

        Blade::directive('endpermission', function ($permission) {
             return "endif;"; //return this endif statement inside php tag
        });
    }
}
