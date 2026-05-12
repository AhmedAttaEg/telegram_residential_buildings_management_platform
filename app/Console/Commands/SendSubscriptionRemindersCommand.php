<?php

namespace App\Console\Commands;

use App\Services\SubscriptionReminderService;
use Illuminate\Console\Command;

class SendSubscriptionRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch queued subscription reminder notifications to tenant owners.';

    public function __construct(
        private readonly SubscriptionReminderService $subscriptionReminderService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $results = $this->subscriptionReminderService->dispatchDueReminders();

        $this->info(sprintf(
            'Subscription reminders dispatched. Expiration: %d, Grace: %d, Suspension: %d',
            $results['expiration'],
            $results['grace'],
            $results['suspension'],
        ));

        return self::SUCCESS;
    }
}
