<?php

namespace App\Domain\Messaging\Services;

use App\Models\Message;
use App\Models\MessageDeliveryState;
use Illuminate\Support\Facades\DB;

final class MessagingDeliveryService
{
    public function createForRecipients(Message $message, int $senderId): void
    {
        $recipientIds = DB::table('conversation_members')
            ->where('conversation_id', $message->conversation_id)
            ->where('user_id', '!=', $senderId)
            ->pluck('user_id');

        foreach ($recipientIds as $rid) {
            MessageDeliveryState::query()->firstOrCreate(
                [
                    'message_id' => $message->id,
                    'recipient_user_id' => (int) $rid,
                ],
                ['state' => MessageDeliveryState::StatePending]
            );
        }
    }

    /**
     * Mark pending delivery rows as delivered for this recipient when they fetch history.
     *
     * @param  list<int>  $messageIds
     */
    public function markDeliveredForRecipient(int $recipientUserId, array $messageIds): int
    {
        if ($messageIds === []) {
            return 0;
        }

        return MessageDeliveryState::query()
            ->where('recipient_user_id', $recipientUserId)
            ->whereIn('message_id', $messageIds)
            ->where('state', MessageDeliveryState::StatePending)
            ->update(['state' => MessageDeliveryState::StateDelivered, 'updated_at' => now()]);
    }
}
