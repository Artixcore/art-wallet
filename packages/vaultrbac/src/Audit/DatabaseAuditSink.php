<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Audit;

use Artwallet\VaultRbac\Contracts\AuditSink;
use Artwallet\VaultRbac\Exceptions\ConfigurationException;
use Artwallet\VaultRbac\Models\AuditLog;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class DatabaseAuditSink implements AuditSink
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ConfigRepository $config,
        private readonly AuditChainHasher $hasher,
    ) {}

    public function write(AuditRecord $record): void
    {
        $secret = $this->secret();

        $this->db->connection()->transaction(function () use ($record, $secret): void {
            $previous = AuditLog::query()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $prevHash = $previous?->row_hash ?? (string) $this->config->get('vaultrbac.audit.genesis_prev_hash', 'genesis');

            $occurredAt = Carbon::now();

            $canonical = $this->canonicalFields($record, $occurredAt);

            $rowHash = $this->hasher->rowHash($prevHash, $canonical, $secret);
            $signature = $this->config->get('vaultrbac.audit.sign_rows', true)
                ? $this->hasher->signature($rowHash, $secret)
                : null;

            $request = $this->currentRequest();

            AuditLog::query()->insert([
                'occurred_at' => $occurredAt,
                'tenant_id' => $this->nullableBigIntColumn($record->tenantId),
                'actor_type' => $record->actorType,
                'actor_id' => $this->nullableBigIntColumn($record->actorId),
                'subject_type' => $record->subjectType,
                'subject_id' => $this->nullableBigIntColumn($record->subjectId),
                'target_type' => $record->targetType,
                'target_id' => $this->nullableBigIntColumn($record->targetId),
                'action' => $record->action,
                'diff' => json_encode($record->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                'ip_address' => $request?->ip(),
                'user_agent' => $this->truncate($request?->userAgent(), 65535),
                'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
                'device_id' => $this->deviceId($request),
                'request_id' => $request?->header('X-Request-Id') ?? Str::uuid()->toString(),
                'prev_hash' => $prevHash,
                'row_hash' => $rowHash,
                'signature' => $signature,
                'immutable' => true,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function canonicalFields(AuditRecord $record, Carbon $occurredAt): array
    {
        $fields = [
            'action' => $record->action,
            'actor_id' => $record->actorId,
            'actor_type' => $record->actorType,
            'occurred_at' => $occurredAt->toIso8601String(),
            'payload' => $this->ksortRecursive($record->payload),
            'subject_id' => $record->subjectId,
            'subject_type' => $record->subjectType,
            'target_id' => $record->targetId,
            'target_type' => $record->targetType,
            'tenant_id' => $record->tenantId,
        ];

        ksort($fields);

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function ksortRecursive(array $value): array
    {
        ksort($value);
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $value[$k] = $this->ksortRecursive($v);
            }
        }

        return $value;
    }

    private function secret(): string
    {
        $configured = $this->config->get('vaultrbac.audit.secret');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $key = $this->config->get('app.key');
        if (! is_string($key) || $key === '') {
            throw new ConfigurationException(
                'Set vaultrbac.audit.secret or APP_KEY before using DatabaseAuditSink.',
            );
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }

        return $key;
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request instanceof Request ? $request : null;
    }

    private function deviceId(?Request $request): ?string
    {
        if ($request === null) {
            return null;
        }

        $header = (string) $this->config->get('vaultrbac.context.device_header', 'X-Device-Id');
        $value = $request->header($header);

        return $value !== '' && $value !== null ? $value : null;
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max);
    }

    /**
     * Audit table uses unsigned big integers; non-numeric morph keys stay null (full id remains in payload).
     */
    private function nullableBigIntColumn(string|int|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
