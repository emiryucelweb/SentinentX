<?php

namespace App\Services\WebSocket;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GapDetector
{
    private const GAP_THRESHOLD_SECONDS = 30;

    public function detectGap(Carbon $lastMessageTime, ?Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? Carbon::now();
        $gapSeconds = $currentTime->diffInSeconds($lastMessageTime);

        $hasGap = $gapSeconds > self::GAP_THRESHOLD_SECONDS;

        if ($hasGap) {
            Log::warning('WebSocket gap detected', [
                'gap_seconds' => $gapSeconds,
                'last_message' => $lastMessageTime->toISOString(),
                'current_time' => $currentTime->toISOString(),
                'threshold' => self::GAP_THRESHOLD_SECONDS,
            ]);
        }

        return [
            'has_gap' => $hasGap,
            'gap_seconds' => $gapSeconds,
            'threshold' => self::GAP_THRESHOLD_SECONDS,
            'last_message' => $lastMessageTime->toISOString(),
            'current_time' => $currentTime->toISOString(),
            'requires_backfill' => $hasGap,
        ];
    }

    public function getBackfillPeriod(Carbon $lastMessageTime, ?Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ?? Carbon::now();

        // Add 5 second buffer before last message to ensure no missed data
        $backfillStart = $lastMessageTime->subSeconds(5);

        return [
            'start' => $backfillStart,
            'end' => $currentTime,
            'duration_seconds' => $currentTime->diffInSeconds($backfillStart),
        ];
    }

    public function shouldTriggerBackfill(int $gapSeconds): bool
    {
        return $gapSeconds > self::GAP_THRESHOLD_SECONDS;
    }
}
