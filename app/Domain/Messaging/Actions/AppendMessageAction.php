<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Services\MessagingDeliveryService;
use App\Domain\Messaging\Services\MessagingNotifier;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AppendMessageAction
{
    public function __construct(
        private readonly MessagingDeliveryService $delivery,
        private readonly MessagingNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{message: Message, idempotent_replay: bool}
     */
    public function execute(Conversation $conversation, User $sender, array $payload): array
    {
        return DB::transaction(function () use ($conversation, $sender, $payload) {
            $locked = Conversation::query()->lockForUpdate()->findOrFail($conversation->id);

            if (! empty($payload['idempotency_key'])) {
                $existing = Message::query()
                    ->where('conversation_id', $locked->id)
                    ->where('sender_id', $sender->id)
                    ->where('idempotency_key', $payload['idempotency_key'])
                    ->first();
                if ($existing !== null) {
                    return ['message' => $existing, 'idempotent_replay' => true];
                }
            }

            $max = Message::query()
                ->where('conversation_id', $locked->id)
                ->max('message_index');
            $nextIndex = $max === null ? 0 : ((int) $max) + 1;

            $ctBytes = base64_decode((string) $payload['ciphertext'], true);
            $sha = $ctBytes !== false ? hash('sha256', $ctBytes) : null;

            $message = Message::query()->create([
                'conversation_id' => $locked->id,
                'sender_id' => $sender->id,
                'message_index' => $nextIndex,
                'ciphertext' => $payload['ciphertext'],
                'ciphertext_sha256' => $sha,
                'nonce' => $payload['nonce'],
                'alg' => $payload['alg'],
                'version' => $payload['version'],
                'client_message_id' => $payload['client_message_id'] ?? null,
                'idempotency_key' => $payload['idempotency_key'] ?? null,
            ]);

            $locked->update(['last_message_at' => now()]);

            $this->delivery->createForRecipients($message, $sender->id);

            $this->notifier->notifyConversationParticipants($locked, $message, $sender);

            return ['message' => $message->fresh(), 'idempotent_replay' => false];
        });
    }
}
