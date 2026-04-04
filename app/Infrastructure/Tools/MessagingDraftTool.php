<?php

namespace App\Infrastructure\Tools;

use App\Domain\Tools\Contracts\AgentToolInterface;
use App\Models\Agent;
use App\Models\User;

/**
 * Produces draft text only — does not send messages.
 */
final class MessagingDraftTool implements AgentToolInterface
{
    public static function key(): string
    {
        return 'messaging.draft';
    }

    public static function riskTier(): string
    {
        return 'read';
    }

    public function execute(User $user, Agent $agent, array $args): array
    {
        $topic = (string) ($args['topic'] ?? 'reply');
        $draft = 'Draft message (not sent): consider addressing: '.substr($topic, 0, 500);

        return [
            'draft' => $draft,
            'note' => 'User must send manually; agent cannot auto-send.',
        ];
    }
}
