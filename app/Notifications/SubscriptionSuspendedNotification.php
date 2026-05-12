<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Channels\TelegramLogChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $reminderKey,
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
            ->subject('Subscription suspended')
            ->line("{$this->tenant->name} has been suspended.")
            ->line($this->tenant->suspension_reason ?? 'Please review the subscription status.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'reminder_type' => 'subscription_suspended',
            'reminder_key' => $this->reminderKey,
            'suspension_reason' => $this->tenant->suspension_reason,
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
