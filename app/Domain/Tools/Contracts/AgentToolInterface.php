<?php

namespace App\Domain\Tools\Contracts;

use App\Models\Agent;
use App\Models\User;

interface AgentToolInterface
{
    public static function key(): string;

    /**
     * Human-readable risk tier: read | write_low | write_high
     */
    public static function riskTier(): string;

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function execute(User $user, Agent $agent, array $args): array;
}
