<?php

namespace App\Domain\Agents\Services;

use App\Models\ProviderBenchmark;
use App\Models\User;

final class ProviderBenchmarkService
{
    /**
     * @param  array<string, mixed>  $metricsDelta  e.g. latency_ms, success bool
     */
    public function recordObservation(
        User $user,
        string $provider,
        ?string $model,
        array $metricsDelta,
    ): void {
        $row = ProviderBenchmark::query()->firstOrNew([
            'user_id' => $user->id,
            'provider' => $provider,
            'model' => $model,
        ]);

        $metrics = $row->metrics_json ?? [
            'latency_ewma_ms' => null,
            'error_rate_ewma' => 0.0,
            'samples' => 0,
        ];

        $latency = (int) ($metricsDelta['latency_ms'] ?? 0);
        $success = (bool) ($metricsDelta['success'] ?? true);
        $alpha = 0.2;
        $metrics['latency_ewma_ms'] = $metrics['latency_ewma_ms'] === null
            ? $latency
            : (int) round($alpha * $latency + (1 - $alpha) * (float) $metrics['latency_ewma_ms']);
        $metrics['error_rate_ewma'] = $alpha * ($success ? 0.0 : 1.0) + (1 - $alpha) * (float) ($metrics['error_rate_ewma'] ?? 0);
        $metrics['samples'] = (int) ($metrics['samples'] ?? 0) + 1;

        $row->metrics_json = $metrics;
        $row->sample_count = (int) ($row->sample_count ?? 0) + 1;
        $row->window_end = now();
        if ($row->window_start === null) {
            $row->window_start = now();
        }
        $row->save();
    }
}
