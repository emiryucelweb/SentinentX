<?php

declare(strict_types=1);

namespace App\Security\Contracts;

interface HmacSigner
{
    public function sign(string $payload, string $timestamp): string;

    public function verify(string $payload, string $timestamp, string $signature): bool;

    public function isValidTimestamp(string $timestamp): bool;
}
