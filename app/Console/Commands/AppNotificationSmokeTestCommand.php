<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\NotificationInfrastructureSmokeTestNotification;
use Illuminate\Console\Command;

class AppNotificationSmokeTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notification-smoke-test {user : The user ID to notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a queued notification across the configured channels.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->findOrFail((int) $this->argument('user'));

        $user->notify(new NotificationInfrastructureSmokeTestNotification('Notification infrastructure smoke test.'));

        $this->info('Notification smoke test dispatched.');

        return self::SUCCESS;
    }
}
