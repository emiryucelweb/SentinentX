<?php

declare(strict_types=1);

namespace App\Security\Contracts;

interface Allowlist
{
    public function isAllowed(string $ip): bool;

    public function getCidrs(): array;

    public function getMode(): string;
}
