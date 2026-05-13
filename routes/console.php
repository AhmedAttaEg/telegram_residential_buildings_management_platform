<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('subscriptions:send-reminders')->daily();
Schedule::command('backups:run')->dailyAt((string) config('operations.backups.schedule.run_at', '02:00'));
Schedule::command('backups:cleanup')->dailyAt((string) config('operations.backups.schedule.cleanup_at', '02:30'));
