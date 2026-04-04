<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMember extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'muted' => 'boolean',
        ];
    }

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'wrapped_conv_key_ciphertext',
        'wrapped_ck_version',
        'last_read_message_id',
        'last_read_at',
        'muted',
    ];

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Message, $this>
     */
    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }
}
