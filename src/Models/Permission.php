<?php

namespace JangKeyte\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JangKeyte\Services\PermissionRegistrar;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
        'code',
    ];

    protected static function booted(): void
    {
        $flush = static fn () => app(PermissionRegistrar::class)->forgetCachedPermissions();
        static::saved($flush);
        static::deleted($flush);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_permissions');
    }
}
