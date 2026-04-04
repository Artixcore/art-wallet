<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message_index',
        'ciphertext',
        'ciphertext_sha256',
        'nonce',
        'alg',
        'version',
        'client_message_id',
        'idempotency_key',
        'reply_to_id',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return HasMany<MessageDeliveryState, $this>
     */
    public function deliveryStates(): HasMany
    {
        return $this->hasMany(MessageDeliveryState::class);
    }

    /**
     * @return HasOne<MessageAttachment, $this>
     */
    public function attachment(): HasOne
    {
        return $this->hasOne(MessageAttachment::class);
    }
}
