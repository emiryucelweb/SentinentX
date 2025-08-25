<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Billing\GdprService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * GDPR Compliance Controller
 * Handles data export, deletion, and privacy requests
 */
class GdprController extends Controller
{
    public function __construct(
        private readonly GdprService $gdprService
    ) {}

    /**
     * Export all user data (GDPR Article 20 - Data Portability)
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $exportData = $this->gdprService->exportUserData($user->id);

            Log::info('GDPR data export requested', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'timestamp' => now(),
                'export_size' => strlen(json_encode($exportData)),
            ]);

            return response()->json([
                'message' => 'Data export completed successfully',
                'export_date' => now()->toISOString(),
                'user_id' => $user->id,
                'data' => $exportData,
                'format' => 'JSON',
                'compliance' => 'GDPR Article 20',
            ]);

        } catch (\Exception $e) {
            Log::error('GDPR data export failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'error' => 'Data export failed',
                'message' => 'Please contact support if this issue persists',
            ], 500);
        }
    }

    /**
     * Request account deletion (GDPR Article 17 - Right to Erasure)
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $deletionRequest = $this->gdprService->requestAccountDeletion(
                $user->id,
                $request->input('reason')
            );

            Log::warning('GDPR account deletion requested', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'reason' => $request->input('reason'),
                'request_id' => $deletionRequest['request_id'],
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Account deletion request submitted successfully',
                'request_id' => $deletionRequest['request_id'],
                'deletion_date' => $deletionRequest['scheduled_deletion_date'],
                'grace_period_days' => $deletionRequest['grace_period_days'],
                'compliance' => 'GDPR Article 17',
                'notice' => 'You can cancel this request within the grace period by contacting support',
            ]);

        } catch (\Exception $e) {
            Log::error('GDPR account deletion request failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'error' => 'Deletion request failed',
                'message' => 'Please contact support',
            ], 500);
        }
    }

    /**
     * Get privacy information and data processing details
     */
    public function privacyInfo(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $privacyInfo = $this->gdprService->getPrivacyInformation($user->id);

            return response()->json([
                'user_id' => $user->id,
                'data_processing' => $privacyInfo['data_processing'],
                'retention_periods' => $privacyInfo['retention_periods'],
                'third_party_sharing' => $privacyInfo['third_party_sharing'],
                'user_rights' => $privacyInfo['user_rights'],
                'contact_info' => $privacyInfo['contact_info'],
                'last_updated' => $privacyInfo['last_updated'],
                'compliance' => 'GDPR Articles 13-14',
            ]);

        } catch (\Exception $e) {
            Log::error('GDPR privacy info request failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'error' => 'Privacy information request failed',
            ], 500);
        }
    }

    /**
     * Update consent preferences
     */
    public function updateConsent(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'marketing_emails' => 'required|boolean',
            'analytics_tracking' => 'required|boolean',
            'performance_cookies' => 'required|boolean',
            'third_party_sharing' => 'required|boolean',
        ]);

        try {
            $consentSettings = $this->gdprService->updateConsentPreferences(
                $user->id,
                $request->only([
                    'marketing_emails',
                    'analytics_tracking',
                    'performance_cookies',
                    'third_party_sharing',
                ])
            );

            Log::info('GDPR consent preferences updated', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'preferences' => $consentSettings,
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Consent preferences updated successfully',
                'preferences' => $consentSettings,
                'updated_at' => now()->toISOString(),
                'compliance' => 'GDPR Article 7',
            ]);

        } catch (\Exception $e) {
            Log::error('GDPR consent update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'error' => 'Consent update failed',
            ], 500);
        }
    }

    /**
     * Request data correction (GDPR Article 16)
     */
    public function requestCorrection(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'field' => 'required|string|max:100',
            'current_value' => 'required|string|max:500',
            'requested_value' => 'required|string|max:500',
            'justification' => 'required|string|max:1000',
        ]);

        try {
            $correctionRequest = $this->gdprService->requestDataCorrection(
                $user->id,
                $request->only(['field', 'current_value', 'requested_value', 'justification'])
            );

            Log::info('GDPR data correction requested', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'field' => $request->input('field'),
                'request_id' => $correctionRequest['request_id'],
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Data correction request submitted successfully',
                'request_id' => $correctionRequest['request_id'],
                'estimated_resolution' => $correctionRequest['estimated_resolution'],
                'compliance' => 'GDPR Article 16',
            ]);

        } catch (\Exception $e) {
            Log::error('GDPR data correction request failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'error' => 'Data correction request failed',
            ], 500);
        }
    }

    /**
     * Generate GDPR compliance report for data controllers
     */
    public function complianceReport(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check if user has admin privileges
        if (! $user || ! $user->hasRole(['admin', 'dpo'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $report = $this->gdprService->generateComplianceReport(
                $request->input('start_date', now()->subMonth()),
                $request->input('end_date', now())
            );

            Log::info('GDPR compliance report generated', [
                'admin_user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'report_period' => [
                    'start' => $request->input('start_date'),
                    'end' => $request->input('end_date'),
                ],
                'timestamp' => now(),
            ]);

            return response()->json([
                'report' => $report,
                'generated_at' => now()->toISOString(),
                'generated_by' => $user->id,
                'compliance' => 'GDPR Article 5(2) - Accountability',
            ]);

        } catch (\Exception $e) {
            Log::error('GDPR compliance report generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'error' => 'Compliance report generation failed',
            ], 500);
        }
    }
}
