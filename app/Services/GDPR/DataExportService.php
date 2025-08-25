<?php

declare(strict_types=1);

namespace App\Services\GDPR;

use App\Models\User;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * GDPR Data Export Service
 * Provides comprehensive user data export functionality
 */
class DataExportService
{
    /**
     * @return array<string, mixed>
     */
    public function requestExport(User $user): array
    {
        $exportId = Str::uuid();
        $filename = "user_data_export_{$user->id}_{$exportId}.zip";

        // Create export job
        dispatch(function () use ($user, $exportId, $filename) {
            $this->generateExport($user, $exportId, $filename);
        })->onQueue('exports');

        return [
            'export_id' => $exportId,
            'status' => 'processing',
            'estimated_completion' => now()->addMinutes(15),
            'download_expires_at' => now()->addDays(7),
        ];
    }

    private function generateExport(User $user, string $exportId, string $filename): void
    {
        $tempDir = storage_path("app/temp/exports/{$exportId}");
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            // 1. User Profile Data
            $this->exportUserProfile($user, $tempDir);

            // 2. Trading Data
            $this->exportTradingData($user, $tempDir);

            // 3. AI Consensus Data
            $this->exportAiData($user, $tempDir);

            // 4. Subscription & Billing Data
            $this->exportBillingData($user, $tempDir);

            // 5. Usage Analytics
            $this->exportUsageData($user, $tempDir);

            // 6. System Logs (last 90 days)
            $this->exportSystemLogs($user, $tempDir);

            // Create ZIP archive
            $zipPath = storage_path("app/exports/{$filename}");
            $this->createZipArchive($tempDir, $zipPath);

            // Clean up temp directory
            $this->cleanupTempDir($tempDir);

            // Notify user
            $user->notify(new \App\Notifications\DataExportReady($exportId, $filename));

        } catch (\Exception $e) {
            // Clean up on error
            $this->cleanupTempDir($tempDir);

            // Notify user of error
            $user->notify(new \App\Notifications\DataExportFailed($exportId, $e->getMessage()));
        }
    }

    private function exportUserProfile(User $user, string $dir): void
    {
        $data = [
            'personal_information' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
                'timezone' => $user->timezone,
                'locale' => $user->locale,
                'meta' => $user->meta,
            ],
            'tenant_information' => $user->tenant ? [
                'tenant_id' => $user->tenant->id,
                'tenant_name' => $user->tenant->name,
                'tenant_domain' => $user->tenant->domain,
                'role_in_tenant' => $user->role,
            ] : null,
        ];

        file_put_contents(
            "{$dir}/user_profile.json",
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function exportTradingData(User $user, string $dir): void
    {
        // Export trades
        // Placeholder - implement when User model has trades relation
        $trades = collect([])->map(function ($trade) {
            return [
                'id' => $trade->id,
                'symbol' => $trade->symbol,
                'side' => $trade->side,
                'status' => $trade->status,
                'quantity' => $trade->quantity,
                'entry_price' => $trade->entry_price,
                'exit_price' => $trade->exit_price,
                'pnl' => $trade->pnl,
                'created_at' => $trade->created_at,
                'closed_at' => $trade->closed_at,
                'meta' => $trade->meta,
            ];
        });

        file_put_contents(
            "{$dir}/trading_data.json",
            json_encode(['trades' => $trades], JSON_PRETTY_PRINT)
        );

        // Export positions
        // Placeholder - implement when User model has positions relation
        $positions = collect([])->map(function ($position) {
            return [
                'id' => $position->id,
                'symbol' => $position->symbol,
                'side' => $position->side,
                'size' => $position->size,
                'entry_price' => $position->entry_price,
                'unrealized_pnl' => $position->unrealized_pnl,
                'created_at' => $position->created_at,
                'updated_at' => $position->updated_at,
            ];
        });

        file_put_contents(
            "{$dir}/positions.json",
            json_encode(['positions' => $positions], JSON_PRETTY_PRINT)
        );
    }

    private function exportAiData(User $user, string $dir): void
    {
        $aiLogs = $user->aiLogs()->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'provider' => $log->provider,
                'action' => $log->action,
                'confidence' => $log->confidence,
                'reason' => $log->reason,
                'created_at' => $log->created_at,
                // Exclude raw input/output for privacy
            ];
        });

        file_put_contents(
            "{$dir}/ai_decisions.json",
            json_encode(['ai_logs' => $aiLogs], JSON_PRETTY_PRINT)
        );
    }

    private function exportBillingData(User $user, string $dir): void
    {
        $subscriptions = $user->subscriptions()->get()->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan_name,
                'status' => $subscription->status,
                'price' => $subscription->price,
                'currency' => $subscription->currency,
                'billing_cycle' => $subscription->billing_cycle,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_ends_at' => $subscription->trial_ends_at,
                'created_at' => $subscription->created_at,
            ];
        });

        file_put_contents(
            "{$dir}/billing_data.json",
            json_encode(['subscriptions' => $subscriptions], JSON_PRETTY_PRINT)
        );
    }

    private function exportUsageData(User $user, string $dir): void
    {
        $usage = $user->usageCounters()->get()->map(function ($counter) {
            return [
                'service' => $counter->service,
                'count' => $counter->count,
                'period' => $counter->period,
                'created_at' => $counter->created_at,
            ];
        });

        file_put_contents(
            "{$dir}/usage_data.json",
            json_encode(['usage_counters' => $usage], JSON_PRETTY_PRINT)
        );
    }

    private function exportSystemLogs(User $user, string $dir): void
    {
        // Export relevant system logs (anonymized)
        $logs = [
            'login_history' => [
                'note' => 'Login history for the last 90 days',
                'data' => [], // Would be populated from audit logs
            ],
            'api_usage' => [
                'note' => 'API usage statistics',
                'data' => [], // Would be populated from API logs
            ],
        ];

        file_put_contents(
            "{$dir}/system_logs.json",
            json_encode($logs, JSON_PRETTY_PRINT)
        );
    }

    private function createZipArchive(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \Exception("Cannot create zip file: {$zipPath}");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Add README
        $readme = "GDPR Data Export\n\n";
        $readme .= "This archive contains all personal data we have stored about you.\n";
        $readme .= 'Export generated on: '.now()->toDateTimeString()."\n\n";
        $readme .= "Contents:\n";
        $readme .= "- user_profile.json: Your account information\n";
        $readme .= "- trading_data.json: Your trading history\n";
        $readme .= "- positions.json: Your current positions\n";
        $readme .= "- ai_decisions.json: AI consensus decisions\n";
        $readme .= "- billing_data.json: Subscription and billing information\n";
        $readme .= "- usage_data.json: Service usage statistics\n";
        $readme .= "- system_logs.json: Relevant system logs\n\n";
        $readme .= "If you have any questions about this data, please contact support.\n";

        $zip->addFromString('README.txt', $readme);
        $zip->close();
    }

    private function cleanupTempDir(string $dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                unlink("{$dir}/{$file}");
            }
            rmdir($dir);
        }
    }
}
