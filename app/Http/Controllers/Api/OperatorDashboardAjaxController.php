<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Observability\Services\AdminAuditLogger;
use App\Domain\Observability\Services\ObservabilityDashboardService;
use App\Domain\Observability\Services\SystemHealthProbeRunner;
use App\Http\Controllers\Controller;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class OperatorDashboardAjaxController extends Controller
{
    public function __construct(
        private readonly ObservabilityDashboardService $dashboard,
        private readonly SystemHealthProbeRunner $probeRunner,
        private readonly AdminAuditLogger $audit,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $this->authorize(config('observability.permissions.health'));

        $data = $this->dashboard->buildSummary();
        $meta = array_merge(
            AjaxEnvelope::withObservabilityMeta(
                partial: $data['partial'],
                stale: $data['stale'],
                staleSubsystems: $data['stale_subsystems'],
                subsystemStatus: $data['subsystem_status'],
            ),
            ['correlation_id' => (string) Str::uuid()],
        );

        if ($data['partial']) {
            return AjaxEnvelope::partialSuccess(
                __('Some observability data could not be loaded.'),
                ['summary' => $data],
                NotificationSeverity::Warning,
                $meta,
            )->toJsonResponse();
        }

        $severity = $data['stale'] ? NotificationSeverity::Warning : NotificationSeverity::Info;

        if ($data['stale']) {
            $staleEnvelope = new AjaxEnvelope(
                success: true,
                code: AjaxResponseCode::StaleData,
                message: '',
                severity: $severity,
                data: ['summary' => $data],
                meta: $meta,
            );

            return $staleEnvelope->toJsonResponse();
        }

        return AjaxEnvelope::ok(
            '',
            ['summary' => $data],
            $severity,
            null,
            null,
            $meta,
        )->toJsonResponse();
    }

    public function runProbes(Request $request): JsonResponse
    {
        $this->authorize(config('observability.permissions.health_trigger_probe'));

        $this->audit->log(
            eventType: 'operator.health.run_probes',
            actor: $request->user(),
            actorType: 'operator',
            subjectUserId: null,
            resourceType: 'observability',
            resourceId: 'probes',
            sensitivity: 'medium',
            metadata: [],
            request: $request,
        );

        try {
            $this->probeRunner->runAll();
        } catch (\Throwable $e) {
            return AjaxEnvelope::error(
                AjaxResponseCode::ServerError,
                __('Probe run failed.'),
                NotificationSeverity::Danger,
                null,
                null,
                ['correlation_id' => (string) Str::uuid()],
            )->toJsonResponse(500);
        }

        $data = $this->dashboard->buildSummary();
        $meta = AjaxEnvelope::withObservabilityMeta(
            partial: $data['partial'],
            stale: $data['stale'],
            staleSubsystems: $data['stale_subsystems'],
            subsystemStatus: $data['subsystem_status'],
        );
        $meta['correlation_id'] = (string) Str::uuid();

        return AjaxEnvelope::ok(
            __('Health probes updated.'),
            ['summary' => $data],
            NotificationSeverity::Success,
            null,
            null,
            $meta,
        )->toJsonResponse();
    }
}
