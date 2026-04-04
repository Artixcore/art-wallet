<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Services;

use App\Jobs\DeliverOutboundWebhookJob;
use App\Models\IntegrationEndpoint;
use App\Models\WebhookDeliveryLog;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;

final class WebhookDispatcher
{
    public function __construct(
        private readonly OutboundWebhookSigner $signer,
    ) {}

    /**
     * Queue a signed outbound webhook delivery (never includes wallet secrets).
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatchUserEvent(IntegrationEndpoint $endpoint, string $eventType, array $payload): WebhookDeliveryLog
    {
        $plain = $this->requirePlainSecret($endpoint);

        $eventId = (string) Str::uuid();
        $body = [
            'event_type' => $eventType,
            'event_id' => $eventId,
            'occurred_at' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $signed = $this->signer->sign($plain, $body);

        $log = WebhookDeliveryLog::query()->create([
            'integration_endpoint_id' => $endpoint->id,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload_json' => $payload,
            'idempotency_key' => $signed['idempotency_key'],
            'attempt_count' => 0,
            'response_code' => null,
            'status' => WebhookDeliveryLog::STATUS_PENDING,
            'last_error' => null,
            'delivered_at' => null,
            'next_retry_at' => now(),
        ]);

        DeliverOutboundWebhookJob::dispatch($log->id)->onQueue('webhooks');

        return $log;
    }

    private function requirePlainSecret(IntegrationEndpoint $endpoint): string
    {
        try {
            $cipher = $endpoint->secret_cipher;
            if (! is_string($cipher) || $cipher === '') {
                throw new \InvalidArgumentException('Missing webhook secret.');
            }

            return $cipher;
        } catch (DecryptException) {
            throw new \InvalidArgumentException('Invalid webhook secret material.');
        }
    }
}
