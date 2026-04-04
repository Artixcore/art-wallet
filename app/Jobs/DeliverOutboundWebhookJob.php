<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Webhooks\Services\OutboundWebhookSigner;
use App\Models\IntegrationEndpoint;
use App\Models\WebhookDeliveryLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DeliverOutboundWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        public int $webhookDeliveryLogId,
    ) {}

    public function handle(OutboundWebhookSigner $signer): void
    {
        $log = WebhookDeliveryLog::query()->with('integrationEndpoint')->find($this->webhookDeliveryLogId);
        if ($log === null) {
            return;
        }

        $endpoint = $log->integrationEndpoint;
        if ($endpoint === null || ! $endpoint->enabled) {
            $log->update([
                'status' => WebhookDeliveryLog::STATUS_FAILED,
                'last_error' => 'endpoint_disabled',
            ]);

            return;
        }

        $plain = $endpoint->secret_cipher;
        if (! is_string($plain) || $plain === '') {
            $log->update([
                'status' => WebhookDeliveryLog::STATUS_FAILED,
                'last_error' => 'missing_secret',
            ]);

            return;
        }

        $body = [
            'event_type' => $log->event_type,
            'event_id' => $log->event_id,
            'occurred_at' => $log->created_at->toIso8601String(),
            'data' => $log->payload_json ?? [],
        ];

        $signed = $signer->sign($plain, $body);

        $response = Http::timeout(15)
            ->withHeaders([
                'X-ArtWallet-Signature' => $signed['signature'],
                'X-ArtWallet-Timestamp' => $signed['timestamp'],
                'Idempotency-Key' => $log->idempotency_key,
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint->url, $body);

        $attempt = $log->attempt_count + 1;
        $log->update(['attempt_count' => $attempt]);

        if ($response->successful()) {
            $log->update([
                'status' => WebhookDeliveryLog::STATUS_DELIVERED,
                'response_code' => $response->status(),
                'delivered_at' => now(),
                'next_retry_at' => null,
                'last_error' => null,
            ]);

            return;
        }

        $log->update([
            'response_code' => $response->status(),
            'last_error' => substr($response->body(), 0, 2000),
            'next_retry_at' => now()->addSeconds($this->backoff * $attempt),
        ]);

        if ($attempt >= $this->tries) {
            $log->update(['status' => WebhookDeliveryLog::STATUS_DEAD_LETTER]);
            Log::warning('webhook_dead_letter', ['log_id' => $log->id, 'endpoint_id' => $endpoint->id]);

            return;
        }

        $this->release($this->backoff * $attempt);
    }
}
