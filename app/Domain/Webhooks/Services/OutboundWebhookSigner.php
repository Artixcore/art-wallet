<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Services;

use Illuminate\Support\Str;

/**
 * HMAC-SHA256 over canonical JSON (timestamp + body) for partner verification.
 */
final class OutboundWebhookSigner
{
    /**
     * @param  array<string, mixed>  $body
     * @return array{signature: string, timestamp: string, idempotency_key: string}
     */
    public function sign(string $secretPlain, array $body): array
    {
        $timestamp = (string) now()->timestamp;
        $idempotencyKey = isset($body['event_id']) && is_string($body['event_id'])
            ? $body['event_id']
            : (string) Str::uuid();
        $canonical = $timestamp."\n".json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $canonical, $secretPlain);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'idempotency_key' => $idempotencyKey,
        ];
    }

    /**
     * Verifies HMAC over the raw JSON body bytes (must match the signed payload exactly).
     */
    public function verify(string $secretPlain, string $timestamp, string $signature, string $rawJsonBody): bool
    {
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $canonical = $timestamp."\n".$rawJsonBody;
        $expected = hash_hmac('sha256', $canonical, $secretPlain);

        return hash_equals($expected, $signature);
    }
}
