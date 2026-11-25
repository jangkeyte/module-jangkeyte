<?php

namespace JangKeyte\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JangKeyte\Models\Permission;
use JangKeyte\Models\Role;

trait HasPermissionsTrait
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'users_permissions');
    }

    public function isSuperAdmin(): bool
    {
        $superRoles = config('jangkeyte.auth.super_admin_roles', ['administrator']);

        return $this->hasRole(...$superRoles);
    }

    public function givePermissionsTo(string|array ...$permissions): static
    {
        $models = $this->resolvePermissionModels(collect($permissions)->flatten()->all());
        if ($models->isNotEmpty()) {
            $this->permissions()->syncWithoutDetaching($models->pluck('id')->all());
        }

        return $this;
    }

    public function removeAllPermissions(): static
    {
        $this->permissions()->detach();

        return $this;
    }

    public function withdrawPermissionsTo(string|array ...$permissions): static
    {
        $models = $this->resolvePermissionModels(collect($permissions)->flatten()->all());
        if ($models->isNotEmpty()) {
            $this->permissions()->detach($models->pluck('id')->all());
        }

        return $this;
    }

    public function refreshPermissions(string|array ...$permissions): static
    {
        $models = $this->resolvePermissionModels(collect($permissions)->flatten()->all());
        $this->permissions()->sync($models->pluck('id')->all());

        return $this;
    }

    public function hasPermissionTo(Permission|string $permission): bool
    {
        if ($this->hasRole('administrator')) {
            return true;
        }

        $slug = $permission instanceof Permission ? $permission->slug : $permission;

        if ($this->permissions->contains('slug', $slug)) {
            return true;
        }

        if ($permission instanceof Permission && $permission->relationLoaded('roles')) {
            $userRoleIds = $this->roles->pluck('id');

            return $permission->roles->pluck('id')->intersect($userRoleIds)->isNotEmpty();
        }

        $this->loadMissing('roles.permissions');

        foreach ($this->roles as $role) {
            if ($role->permissions->contains('slug', $slug)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->roles->contains('slug', trim($role))) {
                return true;
            }
        }

        return false;
    }

    protected function resolvePermissionModels(array $slugs)
    {
        return Permission::whereIn('slug', $slugs)->get();
    }
}
