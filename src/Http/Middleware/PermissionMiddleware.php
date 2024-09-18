<?php

namespace Modules\Authetication\src\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $permission, $role = null)
    {
        $have_permission = false;
        $permissions = explode("|", $permission);
        foreach ($permissions as $user_permission) {
            if($request->user()->can($user_permission)) {
                $have_permission = true;
            }
        }

        $can_access = false;
        if($role != null) {
            $roles = explode("|", $role);
            foreach ($roles as $user_role) {
                if($request->user()->hasRole($user_role)) {
                    $can_access = true;
                }    
            }
        }        
        
        return $can_access || $have_permission ? $next($request) : abort(401, 'Không đủ quyền thực hiện hoặc truy cập trang này.');
    }
}
