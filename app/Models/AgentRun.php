<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentRun extends Model
{
    protected $fillable = [
        'user_id',
        'agent_id',
        'credential_id',
        'mode',
        'status',
        'outcome',
        'correlation_id',
        'provider',
        'model',
        'latency_ms',
        'cost_estimate_json',
        'usage_json',
        'error_code',
        'error_message',
        'input_summary',
        'output_text',
        'meta_json',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correlation_id' => 'string',
            'cost_estimate_json' => 'array',
            'usage_json' => 'array',
            'meta_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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

    /**
     * @return HasMany<AgentLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AgentLog::class);
    }

    /**
     * @return HasOne<AgentRunFeedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(AgentRunFeedback::class);
    }
}
