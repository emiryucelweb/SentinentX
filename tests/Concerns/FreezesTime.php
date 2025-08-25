<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Carbon\Carbon;

trait FreezesTime
{
    protected bool $timeWasFrozen = false;

    /**
     * Freeze time to a specific timestamp in Europe/Istanbul timezone
     */
    public function freeze(string $timestamp = '2025-01-01 00:00:00'): void
    {
        Carbon::setTestNow(Carbon::parse($timestamp, 'Europe/Istanbul'));
        $this->timeWasFrozen = true;
    }

    /**
     * Unfreeze time and restore normal Carbon behavior
     */
    public function unfreeze(): void
    {
        Carbon::setTestNow();
        $this->timeWasFrozen = false;
    }

    /**
     * Travel to a specific time (custom method to avoid Laravel conflict)
     */
    public function freezeAt(string $timestamp): void
    {
        $this->freeze($timestamp);
    }

    /**
     * Travel forward/backward by a certain interval
     */
    public function travelBy(string $interval): void
    {
        if (! $this->timeWasFrozen) {
            $this->freeze();
        }

        $current = Carbon::getTestNow() ?? Carbon::now();
        $new = $current->modify($interval);
        Carbon::setTestNow($new);
    }

    /**
     * Check if we're currently in a DST transition period
     */
    public function isDstTransition(): bool
    {
        $testNow = Carbon::getTestNow();
        if (! $testNow) {
            return false;
        }

        // Check for Europe/Istanbul DST transitions
        $spring = Carbon::create($testNow->year, 3, 31, 3, 0, 0, 'Europe/Istanbul');
        $fall = Carbon::create($testNow->year, 10, 27, 4, 0, 0, 'Europe/Istanbul');

        return $testNow->between($spring->subHour(), $spring->addHour()) ||
               $testNow->between($fall->subHour(), $fall->addHour());
    }
}
