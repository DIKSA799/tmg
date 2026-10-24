<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const NOTICE_ENABLED = 'notice.enabled';

    public const NOTICE_TITLE = 'notice.title';

    public const NOTICE_BODY = 'notice.body';

    public const TERMS_TITLE = 'terms.title';

    public const TERMS_BODY = 'terms.body';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => Cache::forget(self::cacheKey($setting->key)));
        static::deleted(fn (self $setting) => Cache::forget(self::cacheKey($setting->key)));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(self::cacheKey($key), function () use ($key, $default): ?string {
            $value = static::query()->whereKey($key)->value('value');

            return $value ?? $default;
        });
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function flag(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function flush(): void
    {
        static::query()->pluck('key')->each(fn (string $key) => Cache::forget(self::cacheKey($key)));
    }

    private static function cacheKey(string $key): string
    {
        return 'setting.'.$key;
    }
}
