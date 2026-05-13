<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use App\Notifications\SubscriptionGraceNotification;
use App\Notifications\SubscriptionSuspendedNotification;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SubscriptionReminderService
{
    public function __construct(
        private readonly NotificationRecipientResolver $recipientResolver,
        private readonly TenantSuspensionService $tenantSuspensionService,
    ) {
    }

    /**
     * @return array{expiration:int, grace:int, suspension:int}
     */
    public function dispatchDueReminders(): array
    {
        return [
            'expiration' => $this->sendExpirationReminders(),
            'grace' => $this->sendGraceNotifications(),
            'suspension' => $this->sendSuspensionNotifications(),
        ];
    }

    public function sendExpirationReminders(): int
    {
        $sent = 0;
        $leadDays = (int) config('notifications.subscription_reminders.expiration_lead_days', 7);
        $now = now();
        $windowEnd = $now->copy()->addDays($leadDays);

        $tenants = Tenant::query()
            ->dueTrialExpirationReminders($now, $windowEnd)
            ->get()
            ->concat(
                Tenant::query()
                    ->dueActiveExpirationReminders($now, $windowEnd)
                    ->get()
            )
            ->values();

        $recipientsByTenant = $this->recipientResolver->tenantOwnersForTenants($tenants);

        foreach ($tenants as $tenant) {
            $recipients = $recipientsByTenant->get($tenant->id, collect());

            if ($recipients->isEmpty()) {
                continue;
            }

            $expiresAt = $this->expirationDate($tenant);

            if ($expiresAt === null) {
                continue;
            }

            Notification::send($recipients, new SubscriptionExpiringNotification(
                $tenant,
                'subscription_expiring:'.$expiresAt->toIso8601String(),
                $expiresAt->toDateString(),
            ));

            $this->tenantSuspensionService->markReminderSent($tenant);
            $sent += $recipients->count();
        }

        return $sent;
    }

    public function sendGraceNotifications(): int
    {
        if (! (bool) config('notifications.subscription_reminders.grace_enabled', true)) {
            return 0;
        }

        $sent = 0;

        $tenants = Tenant::query()
            ->dueGraceNotifications()
            ->get();

        $recipientsByTenant = $this->recipientResolver->tenantOwnersForTenants($tenants);
        $reminderKeysByTenant = $this->existingReminderKeysByTenant($recipientsByTenant, SubscriptionGraceNotification::class);

        foreach ($tenants as $tenant) {
            $recipients = $recipientsByTenant->get($tenant->id, collect());

            if ($recipients->isEmpty()) {
                continue;
            }

            $reminderKey = 'subscription_grace:'.$tenant->grace_ends_at?->toIso8601String();

            if (($reminderKeysByTenant[$tenant->id][$reminderKey] ?? false) === true) {
                continue;
            }

            Notification::send($recipients, new SubscriptionGraceNotification(
                $tenant,
                $reminderKey,
                $tenant->grace_ends_at->toDateString(),
            ));

            $sent += $recipients->count();
        }

        return $sent;
    }

    public function sendSuspensionNotifications(): int
    {
        if (! (bool) config('notifications.subscription_reminders.suspension_enabled', true)) {
            return 0;
        }

        $sent = 0;

        $tenants = Tenant::query()
            ->dueSuspensionNotifications()
            ->get();

        $recipientsByTenant = $this->recipientResolver->tenantOwnersForTenants($tenants);
        $reminderKeysByTenant = $this->existingReminderKeysByTenant($recipientsByTenant, SubscriptionSuspendedNotification::class);

        foreach ($tenants as $tenant) {
            $recipients = $recipientsByTenant->get($tenant->id, collect());

            if ($recipients->isEmpty()) {
                continue;
            }

            $reminderKey = 'subscription_suspended:'.$tenant->suspended_at?->toIso8601String();

            if (($reminderKeysByTenant[$tenant->id][$reminderKey] ?? false) === true) {
                continue;
            }

            Notification::send($recipients, new SubscriptionSuspendedNotification(
                $tenant,
                $reminderKey,
            ));

            $sent += $recipients->count();
        }

        return $sent;
    }

    private function expirationDate(Tenant $tenant): ?CarbonInterface
    {
        return match ($tenant->subscription_status) {
            'trial' => $tenant->trial_ends_at,
            'active' => $tenant->subscription_ends_at,
            default => null,
        };
    }

    /**
     * @param  Collection<int, Collection<int, User>>  $recipientsByTenant
     * @return array<int, array<string, bool>>
     */
    private function existingReminderKeysByTenant(Collection $recipientsByTenant, string $notificationClass): array
    {
        $recipientIds = $recipientsByTenant
            ->flatten(1)
            ->pluck('id')
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return [];
        }

        $notifications = DatabaseNotification::query()
            ->whereIn('notifiable_id', $recipientIds)
            ->where('notifiable_type', User::class)
            ->where('type', $notificationClass)
            ->get();

        $keys = [];

        foreach ($notifications as $notification) {
            $data = $notification->data;

            if (! isset($data['tenant_id'], $data['reminder_key'])) {
                continue;
            }

            $keys[(int) $data['tenant_id']][(string) $data['reminder_key']] = true;
        }

        return $keys;
    }
}
