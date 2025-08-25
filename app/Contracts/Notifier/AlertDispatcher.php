<?php

declare(strict_types=1);

namespace App\Contracts\Notifier;

interface AlertDispatcher
{
    /**
     * @param  string  $level  info|warn|error|critical
     * @param  string  $code  kısa kod (örn: RISK_DAILY_CAP, FUNDING_WINDOW)
     * @param  string  $message  kullanıcıya/operatöre gösterilecek metin
     * @param  array  $context  ek veri (JSON-serializable)
     * @param  string|null  $dedupKey  aynı uyarının tekrarını engellemek için anahtar
     */
    public function send(
        string $level,
        string $code,
        string $message,
        array $context = [],
        ?string $dedupKey = null
    ): array;
}
