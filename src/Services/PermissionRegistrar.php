<?php

namespace JangKeyte\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JangKeyte\Models\Permission;
use JangKeyte\Models\Role;

class PermissionRegistrar
{
    public function registerGates(): void
    {
        // Lazy-evaluated RBAC check: zero boot-time DB queries on public pages,
        // and works dynamically after runtime migrations/seeding!
        Gate::before(function ($user, string $ability): ?bool {
            if (! method_exists($user, 'hasPermissionTo')) {
                return null;
            }

            if ($user->hasRole('administrator')) {
                return true;
            }

            $permission = $this->getCachedPermissions()->firstWhere('slug', $ability);
            if ($permission !== null) {
                return $user->hasPermissionTo($permission) ? true : null;
            }

            return null;
        });
    }

    public function getCachedPermissions(): Collection
    {
        try {
            if (! Schema::hasTable('permissions')) {
                return collect();
            }
        } catch (\Throwable) {
            return collect();
        }

        $cacheKey = config('jangkeyte.auth.permission_cache_key', 'jangkeyte.rbac.permissions');
        $ttl = (int) config('jangkeyte.auth.permission_cache_ttl', 86400);

        return Cache::remember($cacheKey, $ttl, static function (): Collection {
            return Permission::with('roles')->orderBy('group')->orderBy('id')->get();
        });
    }

    public function forgetCachedPermissions(): void
    {
        $cacheKey = config('jangkeyte.auth.permission_cache_key', 'jangkeyte.rbac.permissions');
        Cache::forget($cacheKey);
    }

    /**
     * Idempotently create standard 5 CRUD permissions for a resource and assign to super-admin roles.
     *
     * @return Collection<int, Permission>
     */
    public function ensureCrudPermissionsFor(string $resourceName): Collection
    {
        if (! Schema::hasTable('permissions')) {
            return collect();
        }

        $group = Str::headline($resourceName);
        $kebab = Str::kebab($resourceName);

        $actions = [
            'Browse' => 'info',
            'Read' => 'success',
            'Add' => 'primary',
            'Edit' => 'warning',
            'Delete' => 'danger',
        ];

        $created = collect();
        foreach ($actions as $action => $code) {
            $slug = strtolower($action) . '-' . $kebab;
            $permission = Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => "{$action} {$group}",
                    'group' => $group,
                    'description' => "User can {$action} {$group}",
                    'code' => $code,
                ]
            );
            $created->push($permission);
        }

        if (Schema::hasTable('roles') && Schema::hasTable('roles_permissions')) {
            $superRoles = Role::whereIn('slug', config('jangkeyte.auth.super_admin_roles', ['administrator', 'admin']))->get();
            $permissionIds = $created->pluck('id')->all();
            foreach ($superRoles as $role) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }

        $this->forgetCachedPermissions();

        return $created;
    }
}
