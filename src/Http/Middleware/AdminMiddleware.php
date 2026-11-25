<?php

namespace JangKeyte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $request->expectsJson()
                ? abort(401, 'Unauthenticated.')
                : redirect()->route('login');
        }

        $superRoles = config('jangkeyte.auth.super_admin_roles', ['administrator', 'admin']);
        if (
            (method_exists($user, 'hasRole') && $user->hasRole(...$superRoles))
            || $user->can('browse-dashboard')
        ) {
            return $next($request);
        }

        abort(403, __('Bạn không có quyền quản trị để truy cập khu vực này.'));
    }
}
