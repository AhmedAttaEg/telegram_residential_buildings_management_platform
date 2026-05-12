<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramLogChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationInfrastructureSmokeTestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $message,
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
            ->subject('Notification smoke test')
            ->line($this->message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'notification_smoke_test',
            'message' => $this->message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toTelegram(object $notifiable): array
    {
        return [
            'type' => 'notification_smoke_test',
            'message' => $this->message,
        ];
    }
}
