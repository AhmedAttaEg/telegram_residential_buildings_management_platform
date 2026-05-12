<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Channels\TelegramLogChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionGraceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $reminderKey,
        private readonly string $graceEndsAt,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', TelegramLogChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Subscription grace period notice')
            ->line("{$this->tenant->name} is in grace period until {$this->graceEndsAt}.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'reminder_type' => 'subscription_grace',
            'reminder_key' => $this->reminderKey,
            'grace_ends_at' => $this->graceEndsAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toTelegram(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
