<?php

namespace JangKeyte\Models;

use Illuminate\Database\Eloquent\Model;
use JangKeyte\Services\SettingService;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'display_name',
        'value',
        'type',
        'group',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $flush = static fn () => app(SettingService::class)->forgetCache();
        static::saved($flush);
        static::deleted($flush);
    }
}
