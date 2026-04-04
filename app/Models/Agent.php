<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Agent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'type',
        'status',
        'execution_mode',
        'memory_mode',
        'budget_json',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agent $agent): void {
            if ($agent->slug === null || $agent->slug === '') {
                $agent->slug = Str::slug($agent->name).'-'.Str::lower(Str::random(6));
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AgentPromptVersion, $this>
     */
    public function promptVersions(): HasMany
    {
        return $this->hasMany(AgentPromptVersion::class);
    }

    /**
     * @return HasMany<AgentProviderBinding, $this>
     */
    public function providerBindings(): HasMany
    {
        return $this->hasMany(AgentProviderBinding::class);
    }

    /**
     * @return HasMany<AgentTool, $this>
     */
    public function tools(): HasMany
    {
        return $this->hasMany(AgentTool::class);
    }

    /**
     * @return HasOne<AgentSafetyPolicy, $this>
     */
    public function safetyPolicy(): HasOne
    {
        return $this->hasOne(AgentSafetyPolicy::class);
    }

    /**
     * @return HasMany<AgentRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AgentRun::class);
    }

    /**
     * @return HasMany<Workflow, $this>
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }
}
