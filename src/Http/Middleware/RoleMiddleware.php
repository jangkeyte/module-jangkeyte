<?php

namespace JangKeyte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role, ?string $permission = null): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $roles = array_filter(array_map('trim', explode('|', $role)));
        if (method_exists($user, 'hasRole') && $user->hasRole(...$roles)) {
            return $next($request);
        }

        if ($permission !== null) {
            $permissions = array_filter(array_map('trim', explode('|', $permission)));
            foreach ($permissions as $singlePermission) {
                if ($user->can($singlePermission)) {
                    return $next($request);
                }
            }
        }

        abort(403, __('Không đủ quyền thực hiện hoặc truy cập trang này.'));
    }
}
