<?php

namespace App\Domain\Messaging\Services;

use App\Domain\Notifications\Enums\NotificationCategory;
use App\Domain\Notifications\Services\NotificationFactory;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

final class MessagingNotifier
{
    public function __construct(
        private readonly NotificationFactory $notifications,
    ) {}

    public function notifyConversationParticipants(Conversation $conversation, Message $message, User $sender): void
    {
        $members = $conversation->members()->where('user_id', '!=', $sender->id)->get();
        foreach ($members as $member) {
            $user = User::query()->find($member->user_id);
            if ($user === null) {
                continue;
            }
            $this->notifications->createFromCatalogKey(
                $user,
                NotificationCategory::Messaging,
                'messaging.new_message',
                [
                    'conversation_public_id' => (string) $conversation->public_id,
                ],
                [
                    'dedupe_key' => 'messaging:new:'.$message->id,
                    'subject_type' => Conversation::class,
                    'subject_id' => (int) $conversation->id,
                    'action_url' => '/messaging?conversation='.urlencode((string) $conversation->public_id),
                ],
            );
        }
    }

    public function notifySecureSendFailed(User $user, ?string $conversationPublicId = null): void
    {
        $this->notifications->createFromCatalogKey(
            $user,
            NotificationCategory::Messaging,
            'messaging.send_failed',
            $conversationPublicId !== null ? ['conversation_public_id' => $conversationPublicId] : null,
            [
                'severity' => \App\Domain\Notifications\Enums\NotificationSeverity::Danger,
            ],
        );
    }
}
