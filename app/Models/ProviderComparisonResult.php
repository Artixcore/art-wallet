<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderComparisonResult extends Model
{
    protected $fillable = [
        'user_id',
        'agent_id',
        'correlation_id',
        'candidates_json',
        'scores_json',
        'winner_provider',
        'winner_model',
        'meta_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correlation_id' => 'string',
            'candidates_json' => 'array',
            'scores_json' => 'array',
            'meta_json' => 'array',
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
}
