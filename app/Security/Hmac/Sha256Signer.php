<?php

namespace App\Security\Hmac;

use App\Security\Contracts\HmacSigner;

class Sha256Signer implements HmacSigner
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 300
    ) {}

    public function sign(string $payload, string $timestamp): string
    {
        $message = $payload.$timestamp;

        return hash_hmac('sha256', $message, $this->secret);
    }

    public function verify(string $payload, string $timestamp, string $signature): bool
    {
        if (! $this->isValidTimestamp($timestamp)) {
            return false;
        }

        $expected = $this->sign($payload, $timestamp);

        return hash_equals($expected, $signature);
    }

    public function isValidTimestamp(string $timestamp): bool
    {
        $now = time();
        $ts = (int) $timestamp;

        return abs($now - $ts) <= $this->ttl;
    }
}
