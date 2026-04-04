<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'public_wallet_id',
        'vault_version',
        'kdf_params',
        'wallet_vault_ciphertext',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kdf_params' => 'array',
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
     * @return HasMany<WalletAddress, $this>
     */
    public function walletAddresses(): HasMany
    {
        return $this->hasMany(WalletAddress::class);
    }

    /**
     * @return HasMany<TransactionIntent, $this>
     */
    public function transactionIntents(): HasMany
    {
        return $this->hasMany(TransactionIntent::class);
    }

    /**
     * @return HasOne<WalletSetting, $this>
     */
    public function walletSetting(): HasOne
    {
        return $this->hasOne(WalletSetting::class);
    }

    /**
     * @return HasOne<WalletTransactionPolicy, $this>
     */
    public function walletTransactionPolicy(): HasOne
    {
        return $this->hasOne(WalletTransactionPolicy::class);
    }
}
