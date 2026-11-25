<?php

namespace JangKeyte\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use JangKeyte\Contracts\DashboardHandlerInterface;
use JangKeyte\Models\Permission;
use JangKeyte\Models\Role;
use JangKeyte\Models\User;
use JangKeyte\Support\ModuleLoader;

class DefaultDashboardHandler implements DashboardHandlerInterface
{
    public function render(Request $request): View
    {
        $stats = [
            'users' => User::count(),
            'roles' => Role::count(),
            'permissions' => Permission::count(),
            'modules' => count(app(ModuleLoader::class)->enabledModules()),
        ];

        return view('JangKeyte::dashboard.index', compact('stats'));
    }
}
