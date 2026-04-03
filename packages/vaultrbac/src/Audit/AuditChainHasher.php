<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Audit;

/**
 * Tamper-evident hash chain material for {@see DatabaseAuditSink}.
 */
final class AuditChainHasher
{
    /**
     * @param  array<string, mixed>  $canonicalFields  Must be deterministic (sorted at call site).
     */
    public function rowHash(string $previousRowHash, array $canonicalFields, string $secret): string
    {
        $canonical = json_encode($canonicalFields, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $material = $previousRowHash."\n".$canonical;

        return hash_hmac('sha256', $material, $secret);
    }

    public function signature(string $rowHash, string $secret): string
    {
        return hash_hmac('sha256', $rowHash, $secret);
    }
}
