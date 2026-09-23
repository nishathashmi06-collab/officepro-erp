<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        // Permission slugs such as "employees.create" are resolved against the
        // user's role. Model policies (abilities without a dot) run as usual.
        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.')) {
                return $user->hasPermission($ability);
            }

            return null;
        });

        $timezone = Settings::get('timezone', config('app.timezone'));
        if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        config(['app.name' => 'OfficePro']);

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('unreadNotificationCount', $user ? $user->unreadNotifications()->count() : 0);
        });
    }
}
