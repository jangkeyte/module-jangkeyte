<?php

namespace JangKeyte\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use JangKeyte\Traits\Filterable;
use JangKeyte\Traits\HasPermissionsTrait;

class User extends Authenticatable
{
    use Filterable, HasFactory, HasPermissionsTrait, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (empty($user->uid)) {
                $user->uid = (string) Str::uuid();
            }
            if (empty($user->photo)) {
                $user->photo = 'default.png';
            }
        });
    }
}
