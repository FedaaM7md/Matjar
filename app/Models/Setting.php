<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /** @var array<string, string|null> */
    private static array $memo = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        try {
            $value = static::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return $default;
        }

        return self::$memo[$key] = $value ?? $default;
    }

    public static function put(string $key, ?string $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        self::$memo[$key] = $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return $value === null ? $default : in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function forgetMemo(): void
    {
        self::$memo = [];
    }

    public static function tablesReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
