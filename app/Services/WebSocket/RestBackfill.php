<?php

namespace App\Services\WebSocket;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RestBackfill
{
    public function backfillExecutions(string $symbol, Carbon $startTime, Carbon $endTime): array
    {
        try {
            $response = Http::get('https://api-testnet.bybit.com/v5/execution/list', [
                'category' => 'linear',
                'symbol' => $symbol,
                'startTime' => $startTime->timestamp * 1000,
                'endTime' => $endTime->timestamp * 1000,
                'limit' => 100,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $executions = $data['result']['list'] ?? [];

                Log::info('REST backfill completed', [
                    'symbol' => $symbol,
                    'executions_found' => count($executions),
                    'start_time' => $startTime->toISOString(),
                    'end_time' => $endTime->toISOString(),
                ]);

                return $this->processExecutions($executions);
            }

            Log::error('REST backfill API error', [
                'symbol' => $symbol,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('REST backfill exception', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function backfillPositions(?string $symbol = null): array
    {
        try {
            $params = ['category' => 'linear'];
            if ($symbol) {
                $params['symbol'] = $symbol;
            }

            $response = Http::get('https://api-testnet.bybit.com/v5/position/list', $params);

            if ($response->successful()) {
                $data = $response->json();
                $positions = $data['result']['list'] ?? [];

                Log::info('Position backfill completed', [
                    'symbol' => $symbol,
                    'positions_found' => count($positions),
                ]);

                return $positions;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Position backfill exception', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function processExecutions(array $executions): array
    {
        $processed = [];

        foreach ($executions as $execution) {
            $processedExecution = [
                'execId' => $execution['execId'],
                'symbol' => $execution['symbol'],
                'side' => $execution['side'],
                'execQty' => $execution['execQty'],
                'execPrice' => $execution['execPrice'],
                'execTime' => $execution['execTime'],
                'oco_attached' => $this->checkOcoAttachment($execution),
            ];

            $processed[] = $processedExecution;
        }

        return $processed;
    }

    private function checkOcoAttachment(array $execution): bool
    {
        // Check if this execution already has OCO orders attached
        // This is a simplified check - in reality would query order history
        $symbol = $execution['symbol'];
        $execQty = (float) $execution['execQty'];

        // If it's a full position execution, likely has OCO
        return $execQty >= 0.5; // Simplified logic
    }
}
