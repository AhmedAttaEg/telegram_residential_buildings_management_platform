<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TelegramLogChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $route = method_exists($notifiable, 'routeNotificationForTelegram')
            ? $notifiable->routeNotificationForTelegram($notification)
            : null;

        if ($route === null || $route === '') {
            return;
        }

        $payload = $notification->toTelegram($notifiable);

        Log::channel((string) config('services.telegram.notifications.log_channel', 'telegram'))
            ->info('Telegram notification dispatched', [
                'notification' => $notification::class,
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->id ?? null,
                'telegram_route' => $route,
                'payload' => $payload,
            ]);
    }
}
