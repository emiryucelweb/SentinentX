<?php

declare(strict_types=1);

namespace App\Services\Notifier;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class MailNotifier
{
    public function notify(string $subject, string $body): void
    {
        $to = (string) Config::get('notifier.mail.to');
        if (! $to) {
            Log::warning('Mail recipient missing');

            return;
        }
        try {
            Mail::raw($body, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('Mail notify failed', ['err' => $e->getMessage()]);
        }
    }
}
