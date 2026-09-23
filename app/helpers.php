<?php

use App\Support\ActivityLogger;
use App\Support\Settings;
use Illuminate\Support\Carbon;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $formatted = number_format((float) $amount, 2);

        return $withSymbol ? Settings::get('currency_symbol', '$').$formatted : $formatted;
    }
}

if (! function_exists('fmt_date')) {
    function fmt_date(mixed $date, ?string $format = null): string
    {
        if (blank($date)) {
            return '—';
        }

        return Carbon::parse($date)->format($format ?? Settings::get('date_format', 'M d, Y'));
    }
}

if (! function_exists('fmt_time')) {
    function fmt_time(mixed $time): string
    {
        return blank($time) ? '—' : Carbon::parse($time)->format('h:i A');
    }
}

if (! function_exists('activity')) {
    function activity(string $action, string $module, string $description, mixed $record = null): void
    {
        ActivityLogger::log($action, $module, $description, $record);
    }
}

if (! function_exists('label')) {
    /** Turn a snake_case value such as "full_time" into "Full Time". */
    function label(?string $value): string
    {
        return $value === null ? '—' : ucwords(str_replace(['_', '-'], ' ', $value));
    }
}
