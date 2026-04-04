<?php

declare(strict_types=1);

namespace App\Domain\Observability\Services;

use App\Models\RpcHealthCheck;
use App\Models\SystemHealthCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Writes {@see SystemHealthCheck} and {@see RpcHealthCheck} rows. Failures are isolated per probe.
 */
final class SystemHealthProbeRunner
{
    private readonly string $probeVersion;

    public function __construct()
    {
        $this->probeVersion = (string) config('observability.probe_version', '1');
    }

    public function runAll(): void
    {
        $this->probeDatabase();
        $this->probeQueue();
        $this->probeRpcEthereum();
        $this->probeRpcSolana();
    }

    private function upsertCheck(string $subsystem, string $checkKey, string $status, ?int $latencyMs, ?string $errorCode, ?array $detail): void
    {
        SystemHealthCheck::query()->updateOrCreate(
            [
                'subsystem' => $subsystem,
                'check_key' => $checkKey,
            ],
            [
                'status' => $status,
                'observed_at' => now(),
                'latency_ms' => $latencyMs,
                'error_code' => $errorCode,
                'detail_json' => $detail,
                'probe_version' => $this->probeVersion,
            ],
        );
    }

    private function probeDatabase(): void
    {
        $start = hrtime(true);
        try {
            DB::selectOne('select 1 as ok');
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            $this->upsertCheck('database', 'connectivity', 'healthy', $ms, null, ['driver' => (string) config('database.default')]);
        } catch (Throwable $e) {
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            $this->upsertCheck('database', 'connectivity', 'critical', $ms, 'db_error', [
                'exception_class' => $e::class,
                'message' => 'Database probe failed.',
            ]);
        }
    }

    private function probeQueue(): void
    {
        $start = hrtime(true);
        try {
            if (! Schema::hasTable('failed_jobs')) {
                $this->upsertCheck('queue', 'failed_jobs_table', 'unknown', null, 'missing_table', []);

                return;
            }
            $failed = DB::table('failed_jobs')->count();
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            $status = $failed > 0 ? 'warning' : 'healthy';
            $this->upsertCheck('queue', 'failed_jobs', $status, $ms, null, [
                'failed_jobs_count' => $failed,
                'connection' => (string) config('queue.default'),
            ]);
        } catch (Throwable $e) {
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            $this->upsertCheck('queue', 'failed_jobs', 'critical', $ms, 'queue_probe_error', [
                'exception_class' => $e::class,
            ]);
        }
    }

    private function probeRpcEthereum(): void
    {
        $url = (string) config('artwallet_chains.ethereum_rpc_url');
        $start = hrtime(true);
        try {
            $response = Http::timeout(15)->asJson()->post($url, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'eth_blockNumber',
                'params' => [],
            ]);
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            if (! $response->successful()) {
                $this->recordRpc('ETH', $url, 'critical', $ms, 'http_error', ['status' => $response->status()]);

                return;
            }
            $json = $response->json();
            $height = isset($json['result']) ? hexdec((string) $json['result']) : null;
            $this->recordRpc('ETH', $url, 'healthy', $ms, null, ['block_height' => $height]);
        } catch (Throwable $e) {
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            $this->recordRpc('ETH', $url, 'critical', $ms, 'rpc_exception', ['exception_class' => $e::class]);
        }
    }

    private function probeRpcSolana(): void
    {
        $url = (string) config('artwallet_chains.solana_rpc_url');
        $start = hrtime(true);
        try {
            $response = Http::timeout(15)->asJson()->post($url, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getHealth',
                'params' => [],
            ]);
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            if (! $response->successful()) {
                $this->recordRpc('SOL', $url, 'critical', $ms, 'http_error', ['status' => $response->status()]);

                return;
            }
            $json = $response->json();
            $ok = ($json['result'] ?? null) === 'ok';
            $this->recordRpc('SOL', $url, $ok ? 'healthy' : 'degraded', $ms, $ok ? null : 'not_ok', ['result' => $json['result'] ?? null]);
        } catch (Throwable $e) {
            $ms = (int) ((hrtime(true) - $start) / 1_000_000);
            $this->recordRpc('SOL', $url, 'critical', $ms, 'rpc_exception', ['exception_class' => $e::class]);
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function recordRpc(string $chain, string $providerUrl, string $status, int $latencyMs, ?string $errorCode, array $detail): void
    {
        $providerKey = substr(hash('sha256', $providerUrl), 0, 64);
        $detail['rpc_url_host'] = parse_url($providerUrl, PHP_URL_HOST) ?: $providerUrl;

        RpcHealthCheck::query()->updateOrCreate(
            [
                'chain' => $chain,
                'provider' => $providerKey,
            ],
            [
                'status' => $status,
                'observed_at' => now(),
                'block_height' => isset($detail['block_height']) && is_numeric($detail['block_height']) ? (int) $detail['block_height'] : null,
                'latency_ms' => $latencyMs,
                'error_code' => $errorCode,
                'detail_json' => $detail,
                'probe_version' => $this->probeVersion,
            ],
        );

        $this->upsertCheck('rpc', strtolower($chain).'.connectivity', $status === 'healthy' ? 'healthy' : ($status === 'degraded' ? 'degraded' : 'critical'), $latencyMs, $errorCode, [
            'chain' => $chain,
            'provider_host' => $detail['rpc_url_host'] ?? null,
        ]);
    }
}
