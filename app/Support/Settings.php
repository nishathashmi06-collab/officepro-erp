<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Key/value application settings, cached for the lifetime of the cache store.
 */
class Settings
{
    private const CACHE_KEY = 'officepro.settings';

    private static ?array $loaded = null;

    public static function defaults(): array
    {
        return [
            'company_name' => 'OfficePro Demo Company',
            'company_logo' => null,
            'company_email' => 'info@officepro.test',
            'company_phone' => '+1 555 0100',
            'company_address' => '100 Business Avenue, Suite 200, Metro City',
            'company_website' => 'https://officepro.test',
            'timezone' => 'UTC',
            'date_format' => 'M d, Y',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'work_start_time' => '09:00',
            'work_end_time' => '17:00',
            'late_grace_minutes' => '15',
            'default_working_hours' => '8',
            'registration_enabled' => '0',
            'document_expiry_days' => '30',
        ];
    }

    public static function all(): array
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            // Database not migrated yet (e.g. during installation).
            $stored = [];
        }

        return self::$loaded = array_merge(self::defaults(), $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$loaded = null;
    }
}
