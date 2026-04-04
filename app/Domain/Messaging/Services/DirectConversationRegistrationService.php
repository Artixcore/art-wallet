<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Models\Conversation;
use App\Models\ConversationDirectIndex;
use App\Models\MessagingContactPair;

final class DirectConversationRegistrationService
{
    public function __construct(
        private readonly DirectConversationLookupService $lookup,
    ) {}

    public function registerDirectConversation(Conversation $conversation, int $userA, int $userB): void
    {
        [$low, $high] = $this->lookup->orderedPair($userA, $userB);

        ConversationDirectIndex::query()->updateOrCreate(
            [
                'user_low_id' => $low,
                'user_high_id' => $high,
            ],
            [
                'conversation_id' => $conversation->id,
            ],
        );

        MessagingContactPair::query()->firstOrCreate(
            [
                'user_low_id' => $low,
                'user_high_id' => $high,
            ],
        );
    }
}
