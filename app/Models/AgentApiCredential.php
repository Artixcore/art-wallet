<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentApiCredential extends Model
{
    protected $table = 'agent_api_credentials';

    protected $fillable = [
        'user_id',
        'provider',
        'label',
        'encrypted_payload',
        'key_fingerprint',
        'last4',
        'metadata_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
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
     * @return HasMany<AgentProviderBinding, $this>
     */
    public function providerBindings(): HasMany
    {
        return $this->hasMany(AgentProviderBinding::class, 'credential_id');
    }

    /**
     * @return HasOne<ProviderHealthCheck, $this>
     */
    public function latestHealthCheck(): HasOne
    {
        return $this->hasOne(ProviderHealthCheck::class, 'credential_id')->latestOfMany('checked_at');
    }
}
