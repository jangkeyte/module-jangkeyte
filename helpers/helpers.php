<?php

use Illuminate\Support\Str;
use JangKeyte\Services\FileUploadService;
use JangKeyte\Services\SettingService;

if (! function_exists('plural_from_model')) {
    function plural_from_model(object|string $model): string
    {
        $base = is_object($model) ? class_basename($model) : class_basename($model);

        return Str::kebab(Str::pluralStudly($base));
    }
}

if (! function_exists('show_route')) {
    function show_route(object $model, ?string $resource = null): string
    {
        $resource = $resource ?? plural_from_model($model);

        return route("{$resource}.show", $model);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}

if (! function_exists('jangkeyte_builder_enabled')) {
    function jangkeyte_builder_enabled(): bool
    {
        return app()->environment(['local', 'testing'])
            || (bool) config('jangkeyte.builder.enabled', false);
    }
}

if (! function_exists('jangkeyte_media_url')) {
    function jangkeyte_media_url(?string $value, ?string $fallbackFolder = null): ?string
    {
        return app(FileUploadService::class)->resolveMediaUrl($value, $fallbackFolder);
    }
}
