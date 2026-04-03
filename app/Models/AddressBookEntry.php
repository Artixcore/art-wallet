<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressBookEntry extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'supported_network_id',
        'address',
        'notes_ciphertext',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SupportedNetwork, $this>
     */
    public function supportedNetwork(): BelongsTo
    {
        return $this->belongsTo(SupportedNetwork::class);
    }
}
