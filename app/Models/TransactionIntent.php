<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransactionIntent extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_AWAITING_SIGNATURE = 'awaiting_signature';

    public const STATUS_SIGNED = 'signed';

    public const STATUS_BROADCAST_SUBMITTED = 'broadcast_submitted';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'asset_id',
        'supported_network_id',
        'direction',
        'from_address',
        'to_address',
        'amount_atomic',
        'memo',
        'fee_quote_json',
        'intent_hash',
        'status',
        'expires_at',
        'idempotency_client_key',
        'construction_payload_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'construction_payload_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<SupportedNetwork, $this>
     */
    public function supportedNetwork(): BelongsTo
    {
        return $this->belongsTo(SupportedNetwork::class);
    }

    /**
     * @return HasMany<SigningRequest, $this>
     */
    public function signingRequests(): HasMany
    {
        return $this->hasMany(SigningRequest::class);
    }

    /**
     * @return HasOne<SignedTransaction, $this>
     */
    public function signedTransaction(): HasOne
    {
        return $this->hasOne(SignedTransaction::class);
    }

    /**
     * @return HasMany<BroadcastAttempt, $this>
     */
    public function broadcastAttempts(): HasMany
    {
        return $this->hasMany(BroadcastAttempt::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
