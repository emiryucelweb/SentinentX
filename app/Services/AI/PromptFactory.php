<?php

declare(strict_types=1);

namespace App\Services\AI;

final class PromptFactory
{
    /** Open karar mesajları */
    public function buildOpenMessages(array $snapshot, string $stage = 'R1'): array
    {
        $systemBase = 'You are a professional cryptocurrency trading AI. You MUST respond with ONLY valid JSON in this exact format:
{
  "action": "LONG" or "SHORT" or "NONE",
  "confidence": 0-100 integer,
  "leverage": leverage_number_based_on_risk_profile,
  "stop_loss": price number,
  "take_profit": price number,  
  "reason": "brief explanation"
}

CRITICAL RULES:
- Output ONLY the JSON object, no other text
- All prices must be realistic numbers
- Confidence 0-100 integer only
- Choose leverage within the risk profile range (will be averaged with other AIs)
- Reason must be under 100 characters
- No markdown, no formatting, just pure JSON

POSITION SIZE: Use maximum 10% of portfolio balance for any single position.';

        if ($stage === 'STAGE2' && isset($snapshot['stage1_results'])) {
            $system = $systemBase . '

STAGE 1 RESULTS FROM OTHER AIs:
Review the initial decisions from other AI providers below and provide your refined analysis.';
        } else {
            $system = $systemBase;
        }
        
        // Kullanıcı gerekçesi varsa AI'ya özel talimat ekle
        if (isset($snapshot['user_intent']['reason'])) {
            $system .= '

USER SPECIFIC REQUEST:
The user has specifically requested this position with the following reason: "' . $snapshot['user_intent']['reason'] . '"
Consider this user insight in your analysis, but maintain your independent judgment. If the user reason aligns with technical analysis, give it weight. If it conflicts with your analysis, explain why.';
        }

        $user = [
            'stage' => $stage,
            'task' => 'Analyze market data and decide whether to open a leveraged perpetual position',
            'snapshot' => $snapshot,
        ];
        
        // Kullanıcı gerekçesi varsa ekle
        if (isset($snapshot['user_intent']['reason'])) {
            $user['user_request'] = [
                'reason' => $snapshot['user_intent']['reason'],
                'type' => 'specific_position_request',
                'message' => 'User specifically requested this position with reason: ' . $snapshot['user_intent']['reason']
            ];
        }

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode($user, JSON_UNESCAPED_SLASHES)],
        ];
    }

    /** Manage mesajları (ileride kullanılacak) */
    public function buildManageMessages(array $snapshot, string $stage = 'R1'): array
    {
        $system = 'You are managing an existing cryptocurrency position. You MUST respond with ONLY valid JSON in this exact format:
{
  "action": "HOLD" or "CLOSE",
  "confidence": 0-100 integer,
  "new_stop_loss": price number or null,
  "new_take_profit": price number or null,
  "qty_delta_factor": number between -1 and 1 or null,
  "reason": "brief explanation"
}

CRITICAL RULES:
- Output ONLY the JSON object, no other text
- All prices must be realistic numbers
- Confidence 0-100 integer only  
- Reason must be under 100 characters
- No markdown, no formatting, just pure JSON';

        $user = ['stage' => $stage, 'task' => 'Manage existing position', 'snapshot' => $snapshot];

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode($user, JSON_UNESCAPED_SLASHES)],
        ];
    }
}
