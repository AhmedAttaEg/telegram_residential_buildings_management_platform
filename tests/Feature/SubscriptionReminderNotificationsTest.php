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
use Illuminate\Notifications\DatabaseNotification;
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

    public function test_expiring_subscription_command_dispatches_trial_and_active_owner_only_notifications_and_marks_reminders_sent(): void
    {
        $this->fakeQueue();

        config([
            'notifications.subscription_reminders.expiration_lead_days' => 7,
        ]);

        $trialTenant = Tenant::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(2),
            'subscription_ends_at' => now()->addDays(2),
            'reminder_sent_at' => null,
        ]);
        $trialOwner = User::factory()->forTenant($trialTenant)->create();
        $trialOwner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));
        $trialAccountant = User::factory()->forTenant($trialTenant)->create();
        $trialAccountant->roles()->attach(Role::query()->where('slug', 'accountant')->value('id'));

        $activeTenant = Tenant::factory()->create([
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(3),
            'reminder_sent_at' => null,
        ]);
        $activeOwner = User::factory()->forTenant($activeTenant)->create();
        $activeOwner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));
        $activeAccountant = User::factory()->forTenant($activeTenant)->create();
        $activeAccountant->roles()->attach(Role::query()->where('slug', 'accountant')->value('id'));

        $this->artisan('subscriptions:send-reminders')
            ->expectsOutput('Subscription reminders dispatched. Expiration: 2, Grace: 0, Suspension: 0')
            ->assertExitCode(0);

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($trialOwner, $trialAccountant): bool {
            return $job->notification instanceof SubscriptionExpiringNotification
                && $job->notifiables->contains(fn (User $notifiable) => $notifiable->is($trialOwner))
                && ! $job->notifiables->contains(fn (User $notifiable) => $notifiable->is($trialAccountant));
        });
        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($activeOwner, $activeAccountant): bool {
            return $job->notification instanceof SubscriptionExpiringNotification
                && $job->notifiables->contains(fn (User $notifiable) => $notifiable->is($activeOwner))
                && ! $job->notifiables->contains(fn (User $notifiable) => $notifiable->is($activeAccountant));
        });

        $this->assertNotNull($trialTenant->fresh()->reminder_sent_at);
        $this->assertNotNull($activeTenant->fresh()->reminder_sent_at);
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

    public function test_lifecycle_notifications_are_not_dispatched_when_tenants_have_no_owner_recipients(): void
    {
        $this->fakeQueue();

        config([
            'notifications.subscription_reminders.expiration_lead_days' => 7,
        ]);

        Tenant::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
            'subscription_ends_at' => now()->addDays(3),
            'reminder_sent_at' => null,
        ]);
        Tenant::factory()->create([
            'subscription_status' => 'grace',
            'grace_ends_at' => now()->addDay(),
        ]);
        Tenant::factory()->suspended()->create([
            'subscription_status' => 'suspended',
            'suspended_at' => now()->subHour(),
        ]);

        $this->artisan('subscriptions:send-reminders')
            ->expectsOutput('Subscription reminders dispatched. Expiration: 0, Grace: 0, Suspension: 0')
            ->assertExitCode(0);

        Queue::assertNotPushed(SendQueuedNotifications::class);
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

    public function test_grace_and_suspension_notifications_persist_expected_lifecycle_metadata(): void
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
            'suspension_reason' => 'Grace expired',
        ]);
        $suspendedOwner = User::factory()->forTenant($suspendedTenant)->create();
        $suspendedOwner->roles()->attach(Role::query()->where('slug', 'tenant_owner')->value('id'));

        $this->artisan('subscriptions:send-reminders')->assertExitCode(0);

        $graceNotification = DatabaseNotification::query()
            ->where('notifiable_id', $graceOwner->id)
            ->where('type', SubscriptionGraceNotification::class)
            ->sole();
        $suspensionNotification = DatabaseNotification::query()
            ->where('notifiable_id', $suspendedOwner->id)
            ->where('type', SubscriptionSuspendedNotification::class)
            ->sole();

        $this->assertSame('subscription_grace', $graceNotification->data['reminder_type']);
        $this->assertSame($graceTenant->id, $graceNotification->data['tenant_id']);
        $this->assertSame($graceTenant->grace_ends_at?->toDateString(), $graceNotification->data['grace_ends_at']);
        $this->assertStringStartsWith('subscription_grace:', $graceNotification->data['reminder_key']);

        $this->assertSame('subscription_suspended', $suspensionNotification->data['reminder_type']);
        $this->assertSame($suspendedTenant->id, $suspensionNotification->data['tenant_id']);
        $this->assertSame('Grace expired', $suspensionNotification->data['suspension_reason']);
        $this->assertStringStartsWith('subscription_suspended:', $suspensionNotification->data['reminder_key']);
    }
}
