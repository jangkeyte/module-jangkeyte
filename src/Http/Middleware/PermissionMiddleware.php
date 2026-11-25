<?php

namespace JangKeyte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission, ?string $role = null): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $permissions = array_filter(array_map('trim', explode('|', $permission)));
        foreach ($permissions as $singlePermission) {
            if ($user->can($singlePermission)) {
                return $next($request);
            }
        }

        if ($role !== null) {
            $roles = array_filter(array_map('trim', explode('|', $role)));
            if (method_exists($user, 'hasRole') && $user->hasRole(...$roles)) {
                return $next($request);
            }
        }

        abort(403, __('Không đủ quyền thực hiện hoặc truy cập trang này.'));
    }
}
