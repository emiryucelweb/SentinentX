<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Log;

/**
 * Telegram RBAC Service - Role-Based Access Control
 * Handles user authentication and authorization for Telegram commands
 */
class TelegramRbacService
{
    /** Admin users with full access (Emir + whitelisted admins) */
    private array $adminUsers = [
        // Chat ID => User info
        'ADMIN_CHAT_ID_1' => [
            'name' => 'Emir',
            'role' => 'admin',
            'permissions' => ['*'],
            'can_approve_patches' => true,
        ],
    ];

    /** Operator users with read-only access */
    private array $operatorUsers = [
        // Add operator chat IDs here
        'OPERATOR_CHAT_ID_1' => [
            'name' => 'Operator 1',
            'role' => 'operator',
            'permissions' => ['status', 'positions', 'balance', 'pnl', 'help'],
            'can_approve_patches' => false,
        ],
    ];

    /**
     * Check if user is authenticated and get their role info
     */
    public function authenticateUser(string $chatId): ?array
    {
        // Check admin users
        if (isset($this->adminUsers[$chatId])) {
            Log::info('Admin user authenticated', ['chat_id' => $chatId, 'user' => $this->adminUsers[$chatId]['name']]);

            return $this->adminUsers[$chatId];
        }

        // Check operator users
        if (isset($this->operatorUsers[$chatId])) {
            Log::info('Operator user authenticated', ['chat_id' => $chatId, 'user' => $this->operatorUsers[$chatId]['name']]);

            return $this->operatorUsers[$chatId];
        }

        // Use fallback from config (backward compatibility)
        $allowedChatId = config('notifier.telegram.chat_id');
        if ($chatId === $allowedChatId) {
            Log::info('Fallback authentication used', ['chat_id' => $chatId]);

            return [
                'name' => 'Config User',
                'role' => 'admin', // Default to admin for config-based auth
                'permissions' => ['*'],
                'can_approve_patches' => true,
            ];
        }

        Log::warning('Unauthorized Telegram access attempt', ['chat_id' => $chatId]);

        return null;
    }

    /**
     * Check if user has permission to execute specific intent
     */
    public function hasPermission(array $user, string $intent): bool
    {
        $permissions = $user['permissions'] ?? [];

        // Admin wildcard permission
        if (in_array('*', $permissions)) {
            return true;
        }

        // Check specific permission
        if (in_array($intent, $permissions)) {
            return true;
        }

        // Check role-based defaults
        return $this->checkRoleBasedPermission($user['role'] ?? 'unknown', $intent);
    }

    /**
     * Check if user can approve core changes/patches
     */
    public function canApprovePatches(array $user): bool
    {
        return $user['can_approve_patches'] ?? false;
    }

    /**
     * Get role-based default permissions
     */
    private function checkRoleBasedPermission(string $role, string $intent): bool
    {
        $rolePermissions = [
            'admin' => ['*'], // Full access
            'operator' => ['status', 'positions', 'balance', 'pnl', 'help', 'list_positions'], // Read-only
        ];

        $permissions = $rolePermissions[$role] ?? [];

        return in_array('*', $permissions) || in_array($intent, $permissions);
    }

    /**
     * Add new admin user (for dynamic management)
     */
    public function addAdmin(string $chatId, string $name): void
    {
        $this->adminUsers[$chatId] = [
            'name' => $name,
            'role' => 'admin',
            'permissions' => ['*'],
            'can_approve_patches' => true,
        ];

        Log::info('New admin user added', ['chat_id' => $chatId, 'name' => $name]);
    }

    /**
     * Add new operator user
     */
    public function addOperator(string $chatId, string $name): void
    {
        $this->operatorUsers[$chatId] = [
            'name' => $name,
            'role' => 'operator',
            'permissions' => ['status', 'positions', 'balance', 'pnl', 'help'],
            'can_approve_patches' => false,
        ];

        Log::info('New operator user added', ['chat_id' => $chatId, 'name' => $name]);
    }

    /**
     * Get user role summary
     */
    public function getUserRoleSummary(array $user): string
    {
        $role = $user['role'] ?? 'unknown';
        $name = $user['name'] ?? 'Unknown';
        $canApprove = $user['can_approve_patches'] ?? false;

        return "👤 **{$name}**\n".
               "🏷️ Role: {$role}\n".
               '🔑 Patch Approval: '.($canApprove ? '✅' : '❌');
    }
}
