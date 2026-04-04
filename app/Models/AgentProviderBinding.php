<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentProviderBinding extends Model
{
    protected $fillable = [
        'agent_id',
        'credential_id',
        'priority',
        'enabled',
        'model_preferences_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'model_preferences_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return BelongsTo<AgentApiCredential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(AgentApiCredential::class, 'credential_id');
    }
}
