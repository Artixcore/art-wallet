<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Onboarding\Enums\OnboardingState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingSession extends Model
{
    protected $fillable = [
        'user_id',
        'state',
        'step_token_hash',
        'step_token_expires_at',
        'passphrase_attempts',
        'locked_at',
        'challenge_indices',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step_token_expires_at' => 'datetime',
            'locked_at' => 'datetime',
            'challenge_indices' => 'array',
        ];
    }

    public function stateEnum(): OnboardingState
    {
        return OnboardingState::from($this->state);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
