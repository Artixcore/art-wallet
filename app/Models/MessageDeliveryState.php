<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageDeliveryState extends Model
{
    public const StatePending = 'pending';

    public const StateDelivered = 'delivered';

    public const StateFailed = 'failed';

    protected $fillable = [
        'message_id',
        'recipient_user_id',
        'state',
    ];

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
