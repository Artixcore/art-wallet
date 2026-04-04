<?php

namespace App\Domain\Agents\Services;

use App\Domain\Tools\ToolRegistry;
use App\Models\Agent;
use App\Models\User;

final class AgentToolExecutionService
{
    public function __construct(
        private readonly ToolRegistry $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function executeEnabledTool(User $user, Agent $agent, string $toolKey, array $args): array
    {
        $enabled = $agent->tools()
            ->where('tool_key', $toolKey)
            ->where('enabled', true)
            ->exists();

        if (! $enabled) {
            throw new \InvalidArgumentException(__('Tool is not enabled for this agent.'));
        }

        $tool = $this->registry->get($toolKey);

        return $tool->execute($user, $agent, $args);
    }
}
