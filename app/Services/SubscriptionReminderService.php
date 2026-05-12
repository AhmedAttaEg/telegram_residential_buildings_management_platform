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
            ->where('status', 'active')
            ->whereIn('subscription_status', ['trial', 'active'])
            ->get()
            ->filter(function (Tenant $tenant) use ($now, $windowEnd): bool {
                $expiresAt = $this->expirationDate($tenant);

                return $expiresAt !== null
                    && $expiresAt->between($now, $windowEnd)
                    && $tenant->reminder_sent_at === null;
            });

        foreach ($tenants as $tenant) {
            $recipients = $this->recipientResolver->tenantOwners($tenant);

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
            ->where('status', 'active')
            ->where('subscription_status', 'grace')
            ->whereNotNull('grace_ends_at')
            ->get();

        foreach ($tenants as $tenant) {
            $recipients = $this->recipientResolver->tenantOwners($tenant);

            if ($recipients->isEmpty()) {
                continue;
            }

            $reminderKey = 'subscription_grace:'.$tenant->grace_ends_at?->toIso8601String();

            if ($this->hasNotificationBeenSent($recipients, SubscriptionGraceNotification::class, $tenant->id, $reminderKey)) {
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
            ->where('status', 'suspended')
            ->where('subscription_status', 'suspended')
            ->whereNotNull('suspended_at')
            ->get();

        foreach ($tenants as $tenant) {
            $recipients = $this->recipientResolver->tenantOwners($tenant);

            if ($recipients->isEmpty()) {
                continue;
            }

            $reminderKey = 'subscription_suspended:'.$tenant->suspended_at?->toIso8601String();

            if ($this->hasNotificationBeenSent($recipients, SubscriptionSuspendedNotification::class, $tenant->id, $reminderKey)) {
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
     * @param  Collection<int, User>  $recipients
     */
    private function hasNotificationBeenSent(
        Collection $recipients,
        string $notificationClass,
        int $tenantId,
        string $reminderKey,
    ): bool {
        $notifications = DatabaseNotification::query()
            ->whereIn('notifiable_id', $recipients->pluck('id'))
            ->where('notifiable_type', User::class)
            ->where('type', $notificationClass)
            ->get();

        return $notifications->contains(function (DatabaseNotification $notification) use ($tenantId, $reminderKey): bool {
            $data = $notification->data;

            return ($data['tenant_id'] ?? null) === $tenantId
                && ($data['reminder_key'] ?? null) === $reminderKey;
        });
    }
}
