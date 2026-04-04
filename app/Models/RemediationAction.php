<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemediationAction extends Model
{
    protected $fillable = [
        'action_type',
        'payload_hash',
        'idempotency_key',
        'status',
        'error',
        'requested_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
