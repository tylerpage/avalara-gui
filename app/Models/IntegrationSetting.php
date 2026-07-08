<?php

namespace App\Models;

use App\Services\DashboardContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    protected $fillable = ['dashboard_id', 'key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $dashboardId = app(DashboardContext::class)->id();

        $setting = static::query()
            ->where('dashboard_id', $dashboardId)
            ->where('key', $key)
            ->first();

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
        $dashboardId = app(DashboardContext::class)->id();

        $encrypted = is_string($value) && $value !== ''
            ? Crypt::encryptString($value)
            : ($value === null || $value === '' ? null : Crypt::encryptString((string) $value));

        static::query()->updateOrCreate(
            ['dashboard_id' => $dashboardId, 'key' => $key],
            ['value' => $encrypted],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMany(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn (string $key) => [$key => static::get($key)])->all();
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
