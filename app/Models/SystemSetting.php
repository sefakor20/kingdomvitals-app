<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    use HasUuids;

    /**
     * The database connection that should be used by the model.
     * SystemSettings are stored in the central database, not tenant databases.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Get the cache store directly to bypass tenancy's tagging wrapper.
     * The database cache driver doesn't support tags.
     */
    private static function cache(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store(config('cache.default'));
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "system_setting:{$key}";

        return self::cache()->remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            $value = $setting->value;

            if ($setting->is_encrypted && $value) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (\Exception) {
                    return $default;
                }
            }

            // Try to decode JSON (for arrays/objects)
            if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            // Cast boolean strings
            if ($value === 'true') {
                return true;
            }
            if ($value === 'false') {
                return false;
            }

            return $value ?? $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'app', bool $encrypt = false): void
    {
        $storeValue = $value;

        // Convert arrays to JSON for storage
        if (is_array($value)) {
            $storeValue = json_encode($value);
        }

        // Convert booleans to strings for storage
        if (is_bool($value)) {
            $storeValue = $value ? 'true' : 'false';
        }

        if ($encrypt && $storeValue) {
            $storeValue = Crypt::encryptString((string) $storeValue);
        }

        static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $storeValue,
                'is_encrypted' => $encrypt,
            ]
        );

        self::cache()->forget("system_setting:{$key}");
    }

    /**
     * Get all settings for a group.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn (SystemSetting $setting): array => [
                $setting->key => $setting->is_encrypted
                    ? static::get($setting->key)
                    : ($setting->value === 'true' ? true : ($setting->value === 'false' ? false : $setting->value)),
            ])
            ->toArray();
    }

    /**
     * Clear all cached settings.
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            self::cache()->forget("system_setting:{$setting->key}");
        }
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Delete a setting.
     */
    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
        self::cache()->forget("system_setting:{$key}");
    }
}
