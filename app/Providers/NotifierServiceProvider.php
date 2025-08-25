<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Notifier\AlertDispatcher;
use App\Services\Notifier\MailNotifier;
use App\Services\Notifier\SlackNotifier;
use App\Services\Notifier\TelegramNotifier;
use Illuminate\Support\ServiceProvider;

final class NotifierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Notifier services
        $this->app->singleton(TelegramNotifier::class, function ($app) {
            $config = config('notifications.telegram', []);

            return new TelegramNotifier(
                $config['bot_token'] ?? null,
                $config['chat_id'] ?? null,
                $config['webhook_url'] ?? null
            );
        });

        $this->app->singleton(SlackNotifier::class, function ($app) {
            $config = config('notifications.slack', []);

            return new SlackNotifier(
                $config['webhook_url'] ?? null,
                $config['channel'] ?? '#alerts',
                $config['username'] ?? 'SentinentX Bot'
            );
        });

        $this->app->singleton(MailNotifier::class, function ($app) {
            $config = config('notifications.mail', []);

            return new MailNotifier(
                $config['to'] ?? null,
                $config['from'] ?? config('mail.from.address'),
                $config['subject_prefix'] ?? '[SentinentX]'
            );
        });

        // AlertDispatcher binding (AppServiceProvider'da da var ama burada da ekleyelim)
        $this->app->bind(AlertDispatcher::class, function ($app) {
            $enabled = (bool) config('notifications.enabled', true);
            $telegram = config('notifications.telegram.enabled', false)
                ? $app->make(TelegramNotifier::class)
                : null;

            return new \App\Services\Notifier\AlertDispatcher($telegram, $enabled);
        });
    }
}
