<?php

declare(strict_types=1);

namespace App\Services\GDPR;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GDPR Data Deletion Service
 * Handles right to be forgotten requests
 */
class DataDeletionService
{
    public function requestDeletion(User $user, string $reason = ''): array
    {
        $deletionId = \Illuminate\Support\Str::uuid();

        // Create deletion request record
        DB::table('data_deletion_requests')->insert([
            'id' => $deletionId,
            'user_id' => $user->id,
            'email' => $user->email,
            'reason' => $reason,
            'status' => 'pending',
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Schedule deletion job (with 30-day grace period)
        dispatch(function () use ($user, $deletionId) {
            $this->processAccountDeletion($user, $deletionId);
        })->delay(now()->addDays(30))->onQueue('deletions');

        return [
            'deletion_id' => $deletionId,
            'status' => 'scheduled',
            'grace_period_ends' => now()->addDays(30),
            'message' => 'Account deletion scheduled. You have 30 days to cancel this request.',
        ];
    }

    public function cancelDeletion(string $deletionId): bool
    {
        $updated = DB::table('data_deletion_requests')
            ->where('id', $deletionId)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }

    private function processAccountDeletion(User $user, string $deletionId): void
    {
        try {
            DB::transaction(function () use ($user, $deletionId) {
                // 1. Check if deletion was cancelled
                $request = DB::table('data_deletion_requests')->where('id', $deletionId)->first();
                if (! $request || $request->status !== 'pending') {
                    return;
                }

                // 2. Close all open positions first
                $this->closeOpenPositions($user);

                // 3. Anonymize trading data (keep for analytics but remove personal identifiers)
                $this->anonymizeTradingData($user);

                // 4. Delete personal data
                $this->deletePersonalData($user);

                // 5. Delete authentication data
                $this->deleteAuthData($user);

                // 6. Delete subscription data
                $this->deleteSubscriptionData($user);

                // 7. Delete usage counters
                $this->deleteUsageData($user);

                // 8. Anonymize logs
                $this->anonymizeLogs($user);

                // 9. Delete user account
                $user->delete();

                // 10. Update deletion request
                DB::table('data_deletion_requests')
                    ->where('id', $deletionId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                Log::info('Account deletion completed', [
                    'deletion_id' => $deletionId,
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Account deletion failed', [
                'deletion_id' => $deletionId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Update deletion request status
            DB::table('data_deletion_requests')
                ->where('id', $deletionId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    private function closeOpenPositions(User $user): void
    {
        // Close all open positions before deletion
        $openPositions = $user->positions()->where('status', 'open')->get();

        foreach ($openPositions as $position) {
            // This would integrate with trading service to close positions
            $position->update([
                'status' => 'closed',
                'closed_at' => now(),
                'close_reason' => 'account_deletion',
            ]);
        }
    }

    private function anonymizeTradingData(User $user): void
    {
        // Anonymize trades (keep for analytics but remove personal identifiers)
        DB::table('trades')
            ->where('user_id', $user->id)
            ->update([
                'user_id' => null,
                'anonymized_user_hash' => hash('sha256', $user->id.config('app.key')),
                'updated_at' => now(),
            ]);

        // Anonymize positions
        DB::table('positions')
            ->where('user_id', $user->id)
            ->update([
                'user_id' => null,
                'anonymized_user_hash' => hash('sha256', $user->id.config('app.key')),
                'updated_at' => now(),
            ]);
    }

    private function deletePersonalData(User $user): void
    {
        // Delete user settings
        DB::table('user_settings')->where('user_id', $user->id)->delete();

        // Delete user preferences
        DB::table('user_preferences')->where('user_id', $user->id)->delete();

        // Delete notification preferences
        DB::table('notification_preferences')->where('user_id', $user->id)->delete();
    }

    private function deleteAuthData(User $user): void
    {
        // Delete personal access tokens
        DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->delete();

        // Delete password reset tokens
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Delete sessions
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function deleteSubscriptionData(User $user): void
    {
        // Delete subscriptions
        DB::table('subscriptions')->where('user_id', $user->id)->delete();

        // Delete billing information (keep transaction records for legal compliance)
        DB::table('billing_addresses')->where('user_id', $user->id)->delete();
        DB::table('payment_methods')->where('user_id', $user->id)->delete();
    }

    private function deleteUsageData(User $user): void
    {
        // Delete usage counters
        DB::table('usage_counters')->where('user_id', $user->id)->delete();

        // Delete analytics data
        DB::table('user_analytics')->where('user_id', $user->id)->delete();
    }

    private function anonymizeLogs(User $user): void
    {
        // Anonymize AI logs
        DB::table('ai_logs')
            ->where('user_id', $user->id)
            ->update([
                'user_id' => null,
                'anonymized_user_hash' => hash('sha256', $user->id.config('app.key')),
                'input_ctx' => json_encode(['anonymized' => true]),
                'raw_output' => json_encode(['anonymized' => true]),
                'updated_at' => now(),
            ]);

        // Anonymize audit logs
        DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->update([
                'user_id' => null,
                'anonymized_user_hash' => hash('sha256', $user->id.config('app.key')),
                'user_email' => 'deleted@example.com',
                'updated_at' => now(),
            ]);
    }

    /**
     * Get deletion request status
     */
    public function getDeletionStatus(string $deletionId): ?array
    {
        $request = DB::table('data_deletion_requests')->where('id', $deletionId)->first();

        if (! $request) {
            return null;
        }

        return [
            'deletion_id' => $request->id,
            'status' => $request->status,
            'requested_at' => $request->requested_at,
            'grace_period_ends' => $request->requested_at ?
                \Carbon\Carbon::parse($request->requested_at)->addDays(30) : null,
            'completed_at' => $request->completed_at,
            'cancelled_at' => $request->cancelled_at,
            'error_message' => $request->error_message,
        ];
    }
}
