<?php

namespace App\Domain\Agents\Services;

use App\Models\Agent;
use App\Models\AgentPromptVersion;
use App\Models\AgentSafetyPolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AgentManagementService
{
    /**
     * @param  array{name: string, description?: string|null, type?: string, slug?: string|null}  $data
     */
    public function create(User $user, array $data): Agent
    {
        return DB::transaction(function () use ($user, $data): Agent {
            $slug = isset($data['slug']) && $data['slug'] !== ''
                ? Str::slug((string) $data['slug'])
                : Str::slug($data['name']).'-'.Str::lower(Str::random(6));

            $agent = Agent::query()->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'slug' => $slug,
                'type' => $data['type'] ?? 'default',
                'status' => 'active',
                'execution_mode' => 'manual',
                'memory_mode' => 'off',
                'budget_json' => [
                    'max_tokens_per_run' => 4096,
                    'max_cost_usd_per_day' => null,
                    'timeout_seconds' => 120,
                ],
                'description' => $data['description'] ?? null,
            ]);

            AgentPromptVersion::query()->create([
                'agent_id' => $agent->id,
                'version' => 1,
                'system_prompt' => $data['system_prompt'] ?? 'You are a helpful assistant for ArtWallet.',
                'developer_prompt' => $data['developer_prompt'] ?? null,
                'variables_json' => null,
                'checksum' => null,
            ]);

            AgentSafetyPolicy::query()->create([
                'agent_id' => $agent->id,
                'permissions_json' => [
                    'allowed_tool_keys' => [],
                    'max_tool_risk_tier' => 'read',
                    'allow_auto_writes' => false,
                ],
            ]);

            return $agent->fresh(['promptVersions', 'safetyPolicy']);
        });
    }

    /**
     * @param  array{name?: string, description?: string|null, status?: string, budget_json?: array<string, mixed>|null}  $data
     */
    public function update(Agent $agent, array $data): Agent
    {
        $fill = [];
        foreach (['name', 'description', 'status', 'budget_json'] as $k) {
            if (array_key_exists($k, $data)) {
                $fill[$k] = $data[$k];
            }
        }
        if ($fill !== []) {
            $agent->fill($fill);
            $agent->save();
        }

        return $agent->fresh();
    }

    /**
     * @param  array{system_prompt?: string|null, developer_prompt?: string|null}  $data
     */
    public function updatePrompt(Agent $agent, array $data): AgentPromptVersion
    {
        $latest = $agent->promptVersions()->orderByDesc('version')->first();
        $nextVersion = ($latest?->version ?? 0) + 1;

        return AgentPromptVersion::query()->create([
            'agent_id' => $agent->id,
            'version' => $nextVersion,
            'system_prompt' => $data['system_prompt'] ?? $latest?->system_prompt,
            'developer_prompt' => $data['developer_prompt'] ?? $latest?->developer_prompt,
            'variables_json' => $latest?->variables_json,
            'checksum' => hash('sha256', (string) ($data['system_prompt'] ?? '').(string) ($data['developer_prompt'] ?? '')),
        ]);
    }

    public function latestPrompt(Agent $agent): ?AgentPromptVersion
    {
        return $agent->promptVersions()->orderByDesc('version')->first();
    }
}
