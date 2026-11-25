<?php

namespace JangKeyte\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use JangKeyte\Models\Role;
use JangKeyte\Models\Setting;
use JangKeyte\Models\User;
use JangKeyte\Services\PermissionRegistrar;
use JangKeyte\Services\SettingService;

class JangKeyteDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rolesData = [
            [
                'slug' => 'administrator',
                'name' => 'Quản trị viên tối cao',
                'description' => 'Quản trị viên có quyền điều hành cao nhất toàn hệ thống.',
                'code' => 'danger',
            ],
            [
                'slug' => 'admin',
                'name' => 'Quản trị viên',
                'description' => 'Quản trị viên điều hành hệ thống.',
                'code' => 'primary',
            ],
            [
                'slug' => 'manager',
                'name' => 'Quản lý',
                'description' => 'Phụ trách quản lý và kiểm soát nghiệp vụ.',
                'code' => 'success',
            ],
            [
                'slug' => 'leader',
                'name' => 'Trưởng nhóm',
                'description' => 'Phụ trách điều phối nhóm.',
                'code' => 'warning text-dark',
            ],
            [
                'slug' => 'user',
                'name' => 'Người dùng',
                'description' => 'Tài khoản thành viên mặc định.',
                'code' => 'secondary',
            ],
        ];

        $roles = [];
        foreach ($rolesData as $item) {
            $roles[$item['slug']] = Role::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }

        // Seed core permissions via PermissionRegistrar (idempotent & auto-assigned to super admin roles)
        $registrar = app(PermissionRegistrar::class);
        foreach (['Dashboard', 'User', 'Role', 'Setting', 'Media', 'Module'] as $resource) {
            $registrar->ensureCrudPermissionsFor($resource);
        }

        // Seed default accounts idempotently
        $defaultAccounts = [
            [
                'username' => 'administrator',
                'name' => 'Administrator',
                'email' => 'administrator@example.com',
                'role' => 'administrator',
            ],
            [
                'username' => 'admin',
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'username' => 'user',
                'name' => 'User Demo',
                'email' => 'user@example.com',
                'role' => 'user',
            ],
        ];

        foreach ($defaultAccounts as $account) {
            $user = User::firstOrCreate(
                ['username' => $account['username']],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => Hash::make('123456'),
                    'photo' => 'default.png',
                ]
            );

            if (isset($roles[$account['role']])) {
                $user->roles()->syncWithoutDetaching([$roles[$account['role']]->id]);
            }
        }

        // Seed default settings idempotently
        $defaultSettings = [
            [
                'key' => 'site.name',
                'display_name' => 'Site Name',
                'value' => 'JangKeyte Modular Platform',
                'type' => 'text',
                'group' => 'general',
                'order' => 1,
            ],
            [
                'key' => 'site.description',
                'display_name' => 'Site Description',
                'value' => 'Hệ thống quản trị Modular Laravel',
                'type' => 'textarea',
                'group' => 'general',
                'order' => 2,
            ],
            [
                'key' => 'site.maintenance_notice',
                'display_name' => 'Maintenance Notice',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'order' => 3,
            ],
            [
                'key' => 'site.logo',
                'display_name' => 'Site Logo',
                'value' => null,
                'type' => 'image',
                'group' => 'branding',
                'order' => 1,
            ],
            [
                'key' => 'site.favicon',
                'display_name' => 'Site Favicon',
                'value' => null,
                'type' => 'image',
                'group' => 'branding',
                'order' => 2,
            ],
            [
                'key' => 'contact.email',
                'display_name' => 'Contact Email',
                'value' => 'admin@example.com',
                'type' => 'text',
                'group' => 'contact',
                'order' => 1,
            ],
            [
                'key' => 'contact.hotline',
                'display_name' => 'Contact Hotline',
                'value' => '0909 000 000',
                'type' => 'text',
                'group' => 'contact',
                'order' => 2,
            ],
        ];

        foreach ($defaultSettings as $item) {
            Setting::firstOrCreate(
                ['key' => $item['key']],
                $item
            );
        }

        $registrar->forgetCachedPermissions();
        app(SettingService::class)->forgetCache();
    }
}
