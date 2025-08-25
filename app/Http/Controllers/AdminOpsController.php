<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AI\ConsensusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminOpsController extends Controller
{
    public function __construct(
        private readonly ConsensusService $consensus
    ) {}

    /**
     * /admin/open-now endpoint
     */
    public function openNow(Request $request): JsonResponse
    {
        // IP whitelist kontrolü
        if (! $this->checkIpWhitelist($request)) {
            return response()->json(['error' => 'IP not whitelisted'], 403);
        }

        // HMAC signature kontrolü
        if (! $this->verifyHmac($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Manuel validation
        $symbols = $request->input('symbols');
        $snapshot = $request->input('snapshot');

        if (empty($symbols) || ! is_string($symbols)) {
            return response()->json(['errors' => ['symbols' => ['The symbols field is required and must be a string.']]], 422);
        }

        if (empty($snapshot) || ! is_array($snapshot)) {
            return response()->json(['errors' => ['snapshot' => ['The snapshot field is required and must be an array.']]], 422);
        }

        try {
            $symbols = array_map('trim', explode(',', $request->input('symbols')));
            $snapshot = $request->input('snapshot');
            $isDryRun = $request->boolean('dry_run', false);

            // Snapshot validation
            $validationResult = $this->validateSnapshotSchema($snapshot);
            if (! $validationResult['valid']) {
                return response()->json([
                    'error' => 'Invalid snapshot schema',
                    'details' => $validationResult['error'],
                ], 422);
            }

            $payload = array_merge($snapshot, [
                'symbols' => $symbols,
                'dry_run' => $isDryRun,
                'mode' => config('trading.mode'),
                'risk' => config('trading.risk'),
                'execution' => config('trading.execution'),
            ]);

            $result = $this->consensus->decide($payload);

            return response()->json([
                'success' => true,
                'data' => $result,
                'dry_run' => $isDryRun,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Operation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * /admin/status endpoint
     */
    public function status(): JsonResponse
    {
        if (! $this->checkIpWhitelist(request())) {
            return response()->json(['error' => 'IP not whitelisted'], 403);
        }

        return response()->json([
            'status' => 'operational',
            'timestamp' => now()->toISOString(),
            'environment' => config('app.env'),
            'version' => config('app.version', '1.0.0'),
            'services' => [
                'consensus' => 'active',
                'trading' => 'active',
                'risk' => 'active',
                'lab' => 'active',
            ],
        ]);
    }

    /**
     * IP whitelist kontrolü
     */
    private function checkIpWhitelist(Request $request): bool
    {
        $allowedIps = config('admin.allowed_ips', []);
        $clientIp = $request->ip();

        return in_array($clientIp, $allowedIps);
    }

    /**
     * HMAC signature doğrulama
     */
    private function verifyHmac(Request $request): bool
    {
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $payload = $request->getContent();

        if (! $signature || ! $timestamp) {
            return false;
        }

        // Timestamp kontrolü (5 dakika tolerance)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $secret = config('admin.hmac_secret');
        if (! is_string($secret)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.$payload, $secret);

        return hash_equals($expectedSignature, (string) $signature);
    }

    /**
     * Snapshot schema validation
     */
    private function validateSnapshotSchema(array $snap): array
    {
        $requiredFields = ['market_data', 'portfolio', 'risk_context'];

        foreach ($requiredFields as $field) {
            if (! isset($snap[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        // Market data validation
        if (! is_array($snap['market_data']) || empty($snap['market_data'])) {
            return [
                'valid' => false,
                'error' => 'market_data must be a non-empty array',
            ];
        }

        // Portfolio validation
        if (! is_array($snap['portfolio']) || ! isset($snap['portfolio']['equity'])) {
            return [
                'valid' => false,
                'error' => 'portfolio must contain equity field',
            ];
        }

        // Risk context validation
        if (! is_array($snap['risk_context']) || ! isset($snap['risk_context']['im_buffer_factor'])) {
            return [
                'valid' => false,
                'error' => 'risk_context must contain im_buffer_factor field',
            ];
        }

        return ['valid' => true, 'error' => null];
    }
}
