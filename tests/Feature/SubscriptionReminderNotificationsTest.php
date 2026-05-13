<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use App\Notifications\SubscriptionGraceNotification;
use App\Notifications\SubscriptionSuspendedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SubscriptionReminderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_expiring_subscription_command_dispatches_owner_only_notifications_and_marks_reminder_sent(): void
    {
        $this->fakeQueue();

        config([
            'notifications.subscription_reminders.expiration_lead_days' => 7,
        ]);

        $tenant = Tenant::factory()->create([
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(3),
            'reminder_sent_at' => null,
        ]);
        $owner = User::factory()->forTenant($tenant)->create();
        $owner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));
        $accountant = User::factory()->forTenant($tenant)->create();
        $accountant->roles()->attach(Role::query()->where('slug', 'accountant')->value('id'));

        $this->artisan('subscriptions:send-reminders')
            ->expectsOutput('Subscription reminders dispatched. Expiration: 1, Grace: 0, Suspension: 0')
            ->assertExitCode(0);

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($owner, $accountant): bool {
            return $job->notification instanceof SubscriptionExpiringNotification
                && $job->notifiables->contains(fn (User $notifiable) => $notifiable->is($owner))
                && ! $job->notifiables->contains(fn (User $notifiable) => $notifiable->is($accountant));
        });

        $this->assertNotNull($tenant->fresh()->reminder_sent_at);
    }

    public function test_grace_and_suspension_notifications_dispatch_when_due(): void
    {
        $this->fakeQueue();

        $graceTenant = Tenant::factory()->create([
            'subscription_status' => 'grace',
            'grace_ends_at' => now()->addDays(2),
        ]);
        $graceOwner = User::factory()->forTenant($graceTenant)->create();
        $graceOwner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));

        $suspendedTenant = Tenant::factory()->suspended()->create([
            'subscription_status' => 'suspended',
            'suspended_at' => now()->subHour(),
        ]);
        $suspendedOwner = User::factory()->forTenant($suspendedTenant)->create();
        $suspendedOwner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));

        $this->artisan('subscriptions:send-reminders')
            ->expectsOutput('Subscription reminders dispatched. Expiration: 0, Grace: 1, Suspension: 1')
            ->assertExitCode(0);

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof SubscriptionGraceNotification);
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof SubscriptionSuspendedNotification);
    }

    public function test_processed_grace_and_suspension_notifications_are_not_duplicated_on_rerun(): void
    {
        config([
            'queue.default' => 'sync',
            'mail.default' => 'array',
            'services.telegram.notifications.default_chat_id' => null,
        ]);

        $graceTenant = Tenant::factory()->create([
            'subscription_status' => 'grace',
            'grace_ends_at' => now()->addDay(),
        ]);
        $graceOwner = User::factory()->forTenant($graceTenant)->create();
        $graceOwner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));

        $suspendedTenant = Tenant::factory()->suspended()->create([
            'subscription_status' => 'suspended',
            'suspended_at' => now()->subHour(),
        ]);
        $suspendedOwner = User::factory()->forTenant($suspendedTenant)->create();
        $suspendedOwner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 2);

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 2);
    }
}
