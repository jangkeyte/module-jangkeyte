<?php

namespace JangKeyte\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JangKeyte\Models\Setting;

class SettingService
{
    public const CACHE_KEY = 'jangkeyte.settings';

    public function all(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, static function (): array {
            return Setting::query()
                ->orderBy('group')
                ->orderBy('order')
                ->orderBy('id')
                ->pluck('value', 'key')
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        return $all[$key] ?? $default;
    }

    public function set(string $key, mixed $value): Setting
    {
        $setting = Setting::query()->where('key', $key)->first();
        $normalized = $value === null ? null : (string) $value;

        if ($setting !== null) {
            $setting->value = $normalized;
            $setting->save();

            return $setting;
        }

        return Setting::create([
            'key' => $key,
            'display_name' => Str::headline(str_replace('.', ' ', $key)),
            'value' => $normalized,
            'type' => 'text',
            'group' => 'general',
            'order' => 0,
        ]);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function allGrouped(): Collection
    {
        try {
            if (! Schema::hasTable('settings')) {
                return collect();
            }
        } catch (\Throwable) {
            return collect();
        }

        $groupOrder = [
            'general' => 0,
            'branding' => 1,
            'contact' => 2,
        ];

        return Setting::query()
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->groupBy('group')
            ->sortBy(static fn (Collection $items, string $group): int => $groupOrder[$group] ?? 99);
    }
}
