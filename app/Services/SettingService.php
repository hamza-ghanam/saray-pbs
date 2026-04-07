<?php

namespace App\Services;

use App\Models\GeneralSetting;

class SettingService
{
    public function get(string $key, $default = null): mixed
    {
        return cache()->remember("setting_{$key}", 300, function () use ($key, $default) {
            return GeneralSetting::where('key', $key)->value('value') ?? $default;
        });
    }

    public function set(string $key, $value): void
    {
        GeneralSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        cache()->forget("setting_{$key}");
    }
}
