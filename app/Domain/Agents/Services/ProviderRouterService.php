<?php

namespace App\Domain\Agents\Services;

use App\Models\Agent;
use App\Models\AgentApiCredential;
use App\Models\ProviderHealthCheck;
use Illuminate\Support\Collection;

final class ProviderRouterService
{
    /**
     * Ordered credentials for execution (enabled bindings, by priority desc).
     *
     * @return list<AgentApiCredential>
     */
    public function orderedCredentialsForAgent(Agent $agent): array
    {
        $bindings = $agent->providerBindings()
            ->where('enabled', true)
            ->orderByDesc('priority')
            ->with('credential')
            ->get();

        /** @var Collection<int, AgentApiCredential> $out */
        $out = $bindings->map(fn ($b) => $b->credential)->filter();

        return $out->unique('id')->values()->all();
    }

    /**
     * Prefer credentials whose latest health is not "error" when possible.
     *
     * @return list<AgentApiCredential>
     */
    public function orderedHealthyFirst(Agent $agent): array
    {
        $creds = $this->orderedCredentialsForAgent($agent);
        usort($creds, function (AgentApiCredential $a, AgentApiCredential $b): int {
            $score = function (AgentApiCredential $c): int {
                $h = ProviderHealthCheck::query()
                    ->where('credential_id', $c->id)
                    ->orderByDesc('checked_at')
                    ->first();

                if ($h === null) {
                    return 1;
                }
                if ($h->status === 'error') {
                    return 0;
                }

                return 2;
            };

            return $score($b) <=> $score($a);
        });

        return $creds;
    }
}
