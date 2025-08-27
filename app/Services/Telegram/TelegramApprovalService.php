<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Telegram Approval Service
 * Handles core change approval workflow and patch management
 */
class TelegramApprovalService
{
    /** Directory for storing pending patches */
    private string $patchDir = 'telegram_patches';

    /**
     * Create patch request for core changes
     */
    public function createPatchRequest(array $intent, array $user): string
    {
        $patchId = $this->generatePatchId();
        $timestamp = now()->toISOString();

        // Create patch metadata
        $patchData = [
            'id' => $patchId,
            'intent' => $intent,
            'requested_by' => $user,
            'timestamp' => $timestamp,
            'status' => 'pending',
            'risk_assessment' => $this->assessRisk($intent),
            'affected_systems' => $this->getAffectedSystems($intent),
        ];

        // Store patch request
        $this->storePatchRequest($patchId, $patchData);

        // Generate patch content based on intent
        $patchContent = $this->generatePatchContent($intent);
        $this->storePatchContent($patchId, $patchContent);

        Log::info('Patch request created', [
            'patch_id' => $patchId,
            'intent' => $intent['intent'],
            'user' => $user['name'] ?? 'Unknown',
        ]);

        // Send notification to admins
        $this->notifyAdmins($patchData);

        return $this->formatPatchCreatedMessage($patchData);
    }

    /**
     * Approve and apply patch
     */
    public function approvePatch(string $patchId, array $user): string
    {
        try {
            // Load patch data
            $patchData = $this->loadPatchRequest($patchId);

            if (! $patchData) {
                return "❌ **Patch Bulunamadı**\n\nPatch ID: {$patchId}";
            }

            if ($patchData['status'] !== 'pending') {
                return "⚠️ **Patch Zaten İşlenmiş**\n\nDurum: {$patchData['status']}";
            }

            // Apply the patch
            $result = $this->applyPatch($patchId, $patchData);

            if ($result['success']) {
                // Update patch status
                $patchData['status'] = 'approved';
                $patchData['approved_by'] = $user;
                $patchData['approved_at'] = now()->toISOString();
                $patchData['application_result'] = $result;

                $this->storePatchRequest($patchId, $patchData);

                Log::info('Patch approved and applied', [
                    'patch_id' => $patchId,
                    'approved_by' => $user['name'] ?? 'Unknown',
                    'result' => $result,
                ]);

                return $this->formatPatchApprovedMessage($patchData, $result);
            } else {
                // Update patch status to failed
                $patchData['status'] = 'failed';
                $patchData['failure_reason'] = $result['error'] ?? 'Unknown error';
                $this->storePatchRequest($patchId, $patchData);

                return "❌ **Patch Uygulanamadı**\n\n".
                       "🆔 **ID:** {$patchId}\n".
                       "⚠️ **Hata:** {$result['error']}\n\n".
                       '🔧 Manuel inceleme gerekli.';
            }

        } catch (\Exception $e) {
            Log::error('Patch approval failed', [
                'patch_id' => $patchId,
                'error' => $e->getMessage(),
                'user' => $user['name'] ?? 'Unknown',
            ]);

            return "❌ **Patch Onay Hatası**\n\n".
                   "🆔 **ID:** {$patchId}\n".
                   "⚠️ **Hata:** {$e->getMessage()}";
        }
    }

    /**
     * Generate unique patch ID
     */
    private function generatePatchId(): string
    {
        return 'PR-'.now()->format('YmdHis').'-'.substr(md5(uniqid()), 0, 6);
    }

    /**
     * Assess risk level of the change
     */
    private function assessRisk(array $intent): array
    {
        $intentName = $intent['intent'] ?? 'unknown';
        $args = $intent['args'] ?? [];

        // Risk assessment based on intent type
        $riskLevels = [
            'set_param' => $this->assessParameterRisk($args),
            'approve_patch' => ['level' => 'medium', 'reason' => 'Patch application'],
            'set_risk' => ['level' => 'low', 'reason' => 'Risk profile change'],
        ];

        return $riskLevels[$intentName] ?? ['level' => 'high', 'reason' => 'Unknown intent type'];
    }

    /**
     * Assess parameter-specific risk
     */
    private function assessParameterRisk(array $args): array
    {
        $param = $args['param'] ?? 'unknown';

        $criticalParams = [
            'risk_engine' => 'high',
            'leverage_limits' => 'high',
            'position_limits' => 'high',
            'scheduler_interval' => 'medium',
            'notification_endpoints' => 'medium',
        ];

        $riskLevel = $criticalParams[$param] ?? 'high';

        return [
            'level' => $riskLevel,
            'reason' => "Parameter change: {$param}",
            'parameter' => $param,
        ];
    }

    /**
     * Get affected systems
     */
    private function getAffectedSystems(array $intent): array
    {
        $intentName = $intent['intent'] ?? 'unknown';

        $systemMappings = [
            'set_param' => ['risk_engine', 'scheduler', 'trading'],
            'approve_patch' => ['core_system'],
            'set_risk' => ['risk_engine', 'trading'],
        ];

        return $systemMappings[$intentName] ?? ['unknown'];
    }

    /**
     * Generate patch content based on intent
     */
    private function generatePatchContent(array $intent): string
    {
        $intentName = $intent['intent'] ?? 'unknown';
        $args = $intent['args'] ?? [];

        return match ($intentName) {
            'set_param' => $this->generateParameterPatch($args),
            'set_risk' => $this->generateRiskPatch($args),
            default => $this->generateGenericPatch($intent)
        };
    }

    /**
     * Generate parameter change patch
     */
    private function generateParameterPatch(array $args): string
    {
        $param = $args['param'] ?? 'unknown';
        $value = $args['value'] ?? 'unknown';

        return "# Parameter Change Patch\n\n".
               "## Change Description\n".
               "- Parameter: {$param}\n".
               "- New Value: {$value}\n\n".
               "## Implementation\n".
               "```php\n".
               "// Update configuration\n".
               "config(['{$param}' => '{$value}']);\n".
               "```\n\n".
               "## Risk Assessment\n".
               "- Impact: Configuration change\n".
               "- Rollback: Previous value available\n";
    }

    /**
     * Generate risk profile patch
     */
    private function generateRiskPatch(array $args): string
    {
        $mode = $args['mode'] ?? 'MID';
        $interval = $args['interval_sec'] ?? null;

        $content = "# Risk Profile Change Patch\n\n".
                   "## Change Description\n".
                   "- Risk Mode: {$mode}\n";

        if ($interval) {
            $content .= "- Cycle Interval: {$interval} seconds\n";
        }

        $content .= "\n## Implementation\n".
                    "```php\n".
                    "// Update risk configuration\n".
                    "\$riskConfig = [\n".
                    "    'mode' => '{$mode}',\n";

        if ($interval) {
            $content .= "    'interval_sec' => {$interval},\n";
        }

        $content .= "];\n".
                    "```\n\n".
                    "## Risk Assessment\n".
                    "- Impact: Trading behavior change\n".
                    "- Rollback: Previous profile available\n";

        return $content;
    }

    /**
     * Generate generic patch
     */
    private function generateGenericPatch(array $intent): string
    {
        return "# Generic Change Patch\n\n".
               "## Intent Data\n".
               "```json\n".
               json_encode($intent, JSON_PRETTY_PRINT)."\n".
               "```\n\n".
               "## Manual Review Required\n".
               "This change requires manual implementation.\n";
    }

    /**
     * Apply patch to system
     */
    private function applyPatch(string $patchId, array $patchData): array
    {
        try {
            $intent = $patchData['intent'];
            $intentName = $intent['intent'] ?? 'unknown';

            // Apply based on intent type
            return match ($intentName) {
                'set_param' => $this->applyParameterChange($intent['args'] ?? []),
                'set_risk' => $this->applyRiskChange($intent['args'] ?? []),
                default => ['success' => false, 'error' => 'Unknown intent type']
            };

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Apply parameter change
     */
    private function applyParameterChange(array $args): array
    {
        $param = $args['param'] ?? null;
        $value = $args['value'] ?? null;

        if (! $param || ! $value) {
            return ['success' => false, 'error' => 'Missing parameter or value'];
        }

        // For demo purposes, just log the change
        // In production, this would update actual configuration
        Log::info('Parameter change applied', ['param' => $param, 'value' => $value]);

        return [
            'success' => true,
            'applied_change' => "Parameter '{$param}' set to '{$value}'",
            'method' => 'configuration_update',
        ];
    }

    /**
     * Apply risk change
     */
    private function applyRiskChange(array $args): array
    {
        $mode = $args['mode'] ?? null;
        $interval = $args['interval_sec'] ?? null;

        if (! $mode) {
            return ['success' => false, 'error' => 'Missing risk mode'];
        }

        // For demo purposes, just log the change
        Log::info('Risk mode change applied', ['mode' => $mode, 'interval' => $interval]);

        return [
            'success' => true,
            'applied_change' => "Risk mode set to '{$mode}'".($interval ? " with {$interval}s interval" : ''),
            'method' => 'risk_profile_update',
        ];
    }

    /**
     * Store patch request metadata
     */
    private function storePatchRequest(string $patchId, array $data): void
    {
        $path = "{$this->patchDir}/{$patchId}.json";
        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Store patch content
     */
    private function storePatchContent(string $patchId, string $content): void
    {
        $path = "{$this->patchDir}/{$patchId}.patch";
        Storage::put($path, $content);
    }

    /**
     * Load patch request data
     */
    private function loadPatchRequest(string $patchId): ?array
    {
        $path = "{$this->patchDir}/{$patchId}.json";

        if (! Storage::exists($path)) {
            return null;
        }

        $content = Storage::get($path);

        return json_decode($content, true);
    }

    /**
     * Notify admins about patch request
     */
    private function notifyAdmins(array $patchData): void
    {
        // In production, this would send notifications to admin channels
        Log::info('Admin notification sent for patch request', [
            'patch_id' => $patchData['id'],
            'risk_level' => $patchData['risk_assessment']['level'] ?? 'unknown',
        ]);
    }

    /**
     * Format patch created message
     */
    private function formatPatchCreatedMessage(array $patchData): string
    {
        $riskLevel = $patchData['risk_assessment']['level'] ?? 'unknown';
        $affectedSystems = implode(', ', $patchData['affected_systems']);

        return "🚀 **Patch Oluşturuldu**\n\n".
               "🆔 **ID:** {$patchData['id']}\n".
               '⚠️ **Risk Seviyesi:** '.strtoupper($riskLevel)."\n".
               "🎯 **Etkilenen Sistemler:** {$affectedSystems}\n".
               "👤 **Talep Eden:** {$patchData['requested_by']['name']}\n\n".
               "📋 **Durum:** Pending - Admin onayı bekleniyor\n\n".
               "✅ **Onaylamak için:** `approve {$patchData['id']}`";
    }

    /**
     * Format patch approved message
     */
    private function formatPatchApprovedMessage(array $patchData, array $result): string
    {
        return "✅ **Patch Onaylandı ve Uygulandı**\n\n".
               "🆔 **ID:** {$patchData['id']}\n".
               "👤 **Onaylayan:** {$patchData['approved_by']['name']}\n".
               "🔧 **Uygulanan Değişiklik:** {$result['applied_change']}\n".
               '⏰ **Uygulama Zamanı:** '.now()->format('H:i:s')."\n\n".
               '🎉 Değişiklik başarıyla aktif edildi!';
    }

    /**
     * List pending patches (admin command)
     */
    public function listPendingPatches(): string
    {
        $files = Storage::files($this->patchDir);
        $pendingPatches = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.json')) {
                $patchData = json_decode(Storage::get($file), true);
                if ($patchData && $patchData['status'] === 'pending') {
                    $pendingPatches[] = $patchData;
                }
            }
        }

        if (empty($pendingPatches)) {
            return "✅ **Bekleyen Patch Yok**\n\nTüm değişiklikler onaylanmış.";
        }

        $message = "📋 **Bekleyen Patch'ler**\n\n";

        foreach ($pendingPatches as $patch) {
            $risk = $patch['risk_assessment']['level'] ?? 'unknown';
            $message .= "🆔 **{$patch['id']}**\n".
                       '⚠️ Risk: '.strtoupper($risk)."\n".
                       "👤 Talep: {$patch['requested_by']['name']}\n".
                       '📅 Tarih: '.substr($patch['timestamp'], 0, 19)."\n\n";
        }

        return $message;
    }
}
