<?php

namespace JangKeyte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BuilderGuardMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! function_exists('jangkeyte_builder_enabled') || ! jangkeyte_builder_enabled()) {
            abort(403, __('Module Studio builder is disabled in this environment.'));
        }

        $user = $request->user();
        if (! $user || ! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin()) {
            abort(403, __('Only super administrators can access the Module Studio builder.'));
        }

        return $next($request);
    }
}
