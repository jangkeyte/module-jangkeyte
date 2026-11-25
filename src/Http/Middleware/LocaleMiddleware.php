<?php

namespace JangKeyte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('jangkeyte.supported_locales', ['vi', 'en']);
        $default = config('jangkeyte.default_locale', config('app.locale', 'vi'));
        $language = $request->hasSession()
            ? $request->session()->get('website_language', $default)
            : $default;

        if (! in_array($language, $supported, true)) {
            $language = $default;
        }

        App::setLocale($language);

        return $next($request);
    }
}
