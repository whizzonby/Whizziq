<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSetting extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Static helper methods for easy access
    public static function get(string $key, $default = null, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();

        $setting = static::where('key', $key)
            ->where('user_id', $userId)
            ->first();

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    public static function set(string $key, $value, ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        $type = static::detectType($value);
        $serializedValue = static::serializeValue($value, $type);

        static::updateOrCreate(
            ['key' => $key, 'user_id' => $userId],
            ['value' => $serializedValue, 'type' => $type]
        );
    }

    public static function forget(string $key, ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        static::where('key', $key)
            ->where('user_id', $userId)
            ->delete();
    }

    public static function has(string $key, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return static::where('key', $key)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function getMultiple(array $keys, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();

        $settings = static::where('user_id', $userId)
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        $result = [];
        foreach ($keys as $key) {
            if ($settings->has($key)) {
                $setting = $settings->get($key);
                $result[$key] = static::castValue($setting->value, $setting->type);
            } else {
                $result[$key] = null;
            }
        }

        return $result;
    }

    public static function setMultiple(array $settings, ?int $userId = null): void
    {
        foreach ($settings as $key => $value) {
            static::set($key, $value, $userId);
        }
    }

    // Helper methods for type handling
    protected static function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return 'json';
        }

        return 'string';
    }

    protected static function serializeValue($value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'array', 'json' => json_encode($value),
            default => (string) $value,
        };
    }

    protected static function castValue(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }
}
