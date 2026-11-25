<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modular Architecture Configuration
    |--------------------------------------------------------------------------
    */
    'modules_path' => base_path('modules'),
    'modules_namespace' => 'Modules',
    'statuses_file' => base_path('modules_statuses.json'),

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    */
    'supported_locales' => ['vi', 'en'],
    'default_locale' => env('APP_LOCALE', 'vi'),

    /*
    |--------------------------------------------------------------------------
    | Authentication & RBAC
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'default_user_role' => 'user',
        'super_admin_roles' => ['administrator', 'admin'],
        'permission_cache_key' => 'jangkeyte.rbac.permissions',
        'permission_cache_ttl' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads & Pagination
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'public_prefix' => 'storage/uploads',
        'private_disk' => 'local',
    ],

    'pagination' => [
        'per_page' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Module & CRUD Studio Builder
    |--------------------------------------------------------------------------
    */
    'builder' => [
        'enabled' => (bool) env('JANGKEYTE_BUILDER_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Centralized Media Manager
    |--------------------------------------------------------------------------
    */
    'media' => [
        'root_dir' => 'storage/uploads',
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
        'max_size_kb' => 5120,
    ],
];
