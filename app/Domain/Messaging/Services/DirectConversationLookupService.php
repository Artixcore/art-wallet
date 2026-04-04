<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Models\Conversation;
use App\Models\ConversationDirectIndex;

final class DirectConversationLookupService
{
    public function orderedPair(int $userA, int $userB): array
    {
        $low = min($userA, $userB);
        $high = max($userA, $userB);

        return [$low, $high];
    }

    public function findConversationIdForPair(int $userA, int $userB): ?int
    {
        [$low, $high] = $this->orderedPair($userA, $userB);

        $row = ConversationDirectIndex::query()
            ->where('user_low_id', $low)
            ->where('user_high_id', $high)
            ->first();

        return $row !== null ? (int) $row->conversation_id : null;
    }

    public function findConversationForPair(int $userA, int $userB): ?Conversation
    {
        $id = $this->findConversationIdForPair($userA, $userB);
        if ($id === null) {
            return null;
        }

        return Conversation::query()->find($id);
    }
}
