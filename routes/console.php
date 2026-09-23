<?php

use Illuminate\Support\Facades\Schedule;

// Run `php artisan schedule:work` (or a cron entry for `schedule:run`) in production.
Schedule::command('officepro:task-deadlines')->dailyAt('08:00');
Schedule::command('officepro:document-expiry')->dailyAt('08:15');
Schedule::command('officepro:mark-absent')->weekdays()->dailyAt('23:30');
