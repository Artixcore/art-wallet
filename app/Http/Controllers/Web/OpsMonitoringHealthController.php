<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Observability\Enums\HealthState;
use App\Domain\Observability\Services\ObservabilityDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Token-gated JSON for external uptime/security monitoring (no session).
 * Reflects observability TTLs and queue failed-job probe from scheduled probes.
 */
final class OpsMonitoringHealthController extends Controller
{
    public function __construct(
        private readonly ObservabilityDashboardService $observability,
    ) {}

    public function show(): JsonResponse
    {
        $summary = $this->observability->buildMonitoringSummary();

        $failStale = (bool) config('observability.monitoring.fail_on_stale', true);
        $failCritical = (bool) config('observability.monitoring.fail_on_critical', true);
        $failPartial = (bool) config('observability.monitoring.fail_on_partial', false);

        $overall = HealthState::tryFrom($summary['overall'] ?? '') ?? HealthState::Unknown;

        $unhealthy = false;
        if ($failStale && ($summary['stale'] ?? false)) {
            $unhealthy = true;
        }
        if ($failCritical && $overall === HealthState::Critical) {
            $unhealthy = true;
        }
        if ($failPartial && ($summary['partial'] ?? false)) {
            $unhealthy = true;
        }

        $status = $unhealthy ? 503 : 200;

        return response()->json($summary, $status);
    }
}
