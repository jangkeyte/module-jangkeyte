<?php

namespace JangKeyte\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JangKeyte\Services\PermissionRegistrar;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'code',
    ];

    protected static function booted(): void
    {
        $flush = static fn () => app(PermissionRegistrar::class)->forgetCachedPermissions();
        static::saved($flush);
        static::deleted($flush);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'roles_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_roles');
    }
}
