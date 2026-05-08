<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GeneralSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // ─── Accessors ───────────────────────────────────────────────

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'integer' => $this->value !== null ? (int) $this->value : null,
            'float'   => $this->value !== null ? (float) $this->value : null,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'json'    => $this->value ? json_decode($this->value, true) : null,
            default   => $this->value,
        };
    }

    // ─── Cache Invalidation ───────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(function (GeneralSetting $setting) {
            Cache::forget("setting:{$setting->key}");
        });

        static::deleted(function (GeneralSetting $setting) {
            Cache::forget("setting:{$setting->key}");
        });
    }

    // ─── Static Helpers ───────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::rememberForever("setting:{$key}", function () use ($key) {
            return static::where('key', $key)->first();
        });

        return $setting ? $setting->typed_value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => $s->typed_value])
            ->toArray();
    }
}
