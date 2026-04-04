<?php

declare(strict_types=1);

namespace App\Domain\Observability\Services;

use App\Domain\Observability\Enums\HealthState;
use App\Models\RpcHealthCheck;
use App\Models\SecurityIncident;
use App\Models\SystemHealthCheck;
use Carbon\CarbonInterface;
use Throwable;

/**
 * Builds operator dashboard payloads with explicit staleness (never false green).
 */
final class ObservabilityDashboardService
{
    /**
     * @return array{
     *   overall: string,
     *   overall_state: string,
     *   partial: bool,
     *   stale: bool,
     *   stale_subsystems: list<string>,
     *   subsystem_status: array<string, string>,
     *   checks: list<array<string, mixed>>,
     *   rpc: list<array<string, mixed>>,
     *   incidents_open: int,
     *   server_time: string,
     *   probe_errors: list<string>
     * }
     */
    public function buildSummary(): array
    {
        $probeErrors = [];
        $checksOut = [];
        $staleSubs = [];
        $subsystemStatus = [];
        $partial = false;

        try {
            $checks = SystemHealthCheck::query()->orderBy('subsystem')->orderBy('check_key')->get();
            foreach ($checks as $c) {
                $effective = $this->effectiveStatus($c->subsystem, $c->status, $c->observed_at);
                if ($effective === HealthState::Stale) {
                    $staleSubs[] = $c->subsystem.'.'.$c->check_key;
                }
                $subsystemStatus[$c->subsystem.'.'.$c->check_key] = $effective->value;
                $checksOut[] = [
                    'subsystem' => $c->subsystem,
                    'check_key' => $c->check_key,
                    'status' => $effective->value,
                    'raw_status' => $c->status,
                    'observed_at' => $c->observed_at?->toIso8601String(),
                    'latency_ms' => $c->latency_ms,
                    'error_code' => $c->error_code,
                    'detail_json' => $c->detail_json,
                    'probe_version' => $c->probe_version,
                    'ttl_seconds' => $this->ttlFor($c->subsystem),
                ];
            }
        } catch (Throwable $e) {
            $partial = true;
            $probeErrors[] = 'system_health_checks: '.$e->getMessage();
        }

        $rpcOut = [];
        try {
            $rpcRows = RpcHealthCheck::query()->orderBy('chain')->get();
            foreach ($rpcRows as $r) {
                $effective = $this->effectiveStatus('rpc', $r->status, $r->observed_at);
                if ($effective === HealthState::Stale) {
                    $staleSubs[] = 'rpc.'.$r->chain;
                }
                $subsystemStatus['rpc.'.$r->chain] = $effective->value;
                $rpcOut[] = [
                    'chain' => $r->chain,
                    'status' => $effective->value,
                    'raw_status' => $r->status,
                    'observed_at' => $r->observed_at?->toIso8601String(),
                    'latency_ms' => $r->latency_ms,
                    'block_height' => $r->block_height,
                    'error_code' => $r->error_code,
                    'detail_json' => $r->detail_json,
                ];
            }
        } catch (Throwable $e) {
            $partial = true;
            $probeErrors[] = 'rpc_health_checks: '.$e->getMessage();
        }

        $incidentsOpen = 0;
        try {
            $incidentsOpen = SecurityIncident::query()->where('state', '!=', 'closed')->count();
        } catch (Throwable $e) {
            $partial = true;
            $probeErrors[] = 'security_incidents: '.$e->getMessage();
        }

        $states = [];
        foreach ($subsystemStatus as $k => $v) {
            $states[] = $this->healthStateFromString($v);
        }
        if ($checksOut === [] && $rpcOut === [] && $probeErrors === []) {
            $overall = HealthState::Unknown;
        } else {
            $overall = $states === [] ? HealthState::Unknown : HealthState::max(...$states);
        }

        $stale = $staleSubs !== [];

        return [
            'overall' => $overall->value,
            'overall_state' => $overall->value,
            'partial' => $partial,
            'stale' => $stale,
            'stale_subsystems' => array_values(array_unique($staleSubs)),
            'subsystem_status' => $subsystemStatus,
            'checks' => $checksOut,
            'rpc' => $rpcOut,
            'incidents_open' => $incidentsOpen,
            'server_time' => now()->toIso8601String(),
            'probe_errors' => $probeErrors,
        ];
    }

    private function ttlFor(string $subsystem): int
    {
        return match (true) {
            str_starts_with($subsystem, 'rpc') => (int) config('observability.ttl_seconds.rpc', 300),
            $subsystem === 'queue' => (int) config('observability.ttl_seconds.queue', 180),
            $subsystem === 'database' => (int) config('observability.ttl_seconds.database', 60),
            default => (int) config('observability.ttl_seconds.default', 120),
        };
    }

    private function effectiveStatus(string $subsystem, string $storedStatus, ?CarbonInterface $observedAt): HealthState
    {
        $ttl = $this->ttlFor($subsystem);
        if ($observedAt === null || $observedAt->lt(now()->subSeconds($ttl))) {
            return HealthState::Stale;
        }

        return match ($storedStatus) {
            'healthy' => HealthState::Healthy,
            'degraded' => HealthState::Degraded,
            'warning' => HealthState::Warning,
            'critical' => HealthState::Critical,
            'unknown' => HealthState::Unknown,
            default => HealthState::Unknown,
        };
    }

    private function healthStateFromString(string $value): HealthState
    {
        return HealthState::tryFrom($value) ?? HealthState::Unknown;
    }
}
