<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting || $setting->value === null) {
            return $default;
        }

        try {
            return Crypt::decryptString($setting->value);
        } catch (\Throwable) {
            return $setting->value;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $encrypted = is_string($value) && $value !== ''
            ? Crypt::encryptString($value)
            : ($value === null || $value === '' ? null : Crypt::encryptString((string) $value));

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $encrypted],
        );
    }

    public static function getMany(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn (string $key) => [$key => static::get($key)])->all();
    }
}
