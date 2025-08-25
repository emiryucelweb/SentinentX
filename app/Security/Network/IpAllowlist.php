<?php

namespace App\Security\Network;

use App\Security\Contracts\Allowlist;

class IpAllowlist implements Allowlist
{
    public function __construct(
        private readonly array $cidrs,
        private readonly string $mode = 'deny'
    ) {}

    public function isAllowed(string $ip): bool
    {
        if (empty($this->cidrs)) {
            return $this->mode === 'allow';
        }

        $isInAllowlist = $this->ipInCidrs($ip, $this->cidrs);

        return match ($this->mode) {
            'allow' => $isInAllowlist,
            'deny' => ! $isInAllowlist,
            default => false,
        };
    }

    public function getCidrs(): array
    {
        return $this->cidrs;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    private function ipInCidrs(string $ip, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$network, $mask] = explode('/', $cidr, 2);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->ipv4InCidr($ip, $network, (int) $mask);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6InCidr($ip, $network, (int) $mask);
        }

        return false;
    }

    private function ipv4InCidr(string $ip, string $network, int $mask): bool
    {
        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);

        if ($ipLong === false || $networkLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($networkLong & $maskLong);
    }

    private function ipv6InCidr(string $ip, string $network, int $mask): bool
    {
        $ipBin = inet_pton($ip);
        $networkBin = inet_pton($network);

        if ($ipBin === false || $networkBin === false) {
            return false;
        }

        $bytes = intval($mask / 8);
        $bits = $mask % 8;

        // Check full bytes
        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($networkBin, 0, $bytes)) {
            return false;
        }

        // Check remaining bits
        if ($bits > 0) {
            $mask = 0xFF << (8 - $bits);
            $ipByte = ord($ipBin[$bytes] ?? "\0");
            $networkByte = ord($networkBin[$bytes] ?? "\0");

            return ($ipByte & $mask) === ($networkByte & $mask);
        }

        return true;
    }
}
