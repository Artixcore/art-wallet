<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SystemHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OpsMonitoringHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_returns_404_when_token_not_configured(): void
    {
        config(['observability.monitoring.token' => '']);

        $this->get(route('ops.monitor.health'))->assertNotFound();
    }

    public function test_monitoring_returns_401_when_token_wrong(): void
    {
        config(['observability.monitoring.token' => 'correct-token']);

        $this->get(route('ops.monitor.health').'?token=wrong')->assertUnauthorized();
    }

    public function test_monitoring_accepts_bearer_token(): void
    {
        config(['observability.monitoring.token' => 'correct-token']);

        $this->withHeader('Authorization', 'Bearer correct-token')
            ->get(route('ops.monitor.health'))
            ->assertOk()
            ->assertJsonStructure([
                'overall',
                'stale',
                'checks',
                'rpc',
                'ttl_config_seconds' => [
                    'default',
                    'queue',
                    'database',
                    'rpc',
                    'notifications',
                ],
            ]);
    }

    public function test_monitoring_returns_503_when_stale_and_fail_on_stale(): void
    {
        config(['observability.monitoring.token' => 'correct-token']);
        config(['observability.monitoring.fail_on_stale' => true]);

        SystemHealthCheck::query()->create([
            'subsystem' => 'database',
            'check_key' => 'connectivity',
            'status' => 'healthy',
            'observed_at' => now()->subHours(2),
            'latency_ms' => 1,
            'error_code' => null,
            'detail_json' => [],
            'probe_version' => '1',
        ]);

        $this->get(route('ops.monitor.health').'?token=correct-token')
            ->assertStatus(503)
            ->assertJsonPath('stale', true);
    }
}
