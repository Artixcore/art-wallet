<?php

namespace App\Infrastructure\Tools;

use App\Domain\Tools\Contracts\AgentToolInterface;
use App\Models\Agent;
use App\Models\User;

/**
 * Read-only: wallet labels and public ids for the authenticated user.
 */
final class WalletSummaryTool implements AgentToolInterface
{
    public static function key(): string
    {
        return 'wallet.summary';
    }

    public static function riskTier(): string
    {
        return 'read';
    }

    public function execute(User $user, Agent $agent, array $args): array
    {
        $wallets = $user->wallets()->select(['id', 'label', 'public_wallet_id', 'created_at'])->get();

        return [
            'wallets' => $wallets->map(fn ($w) => [
                'id' => $w->id,
                'label' => $w->label,
                'public_wallet_id' => $w->public_wallet_id,
            ])->all(),
        ];
    }
}
