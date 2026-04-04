<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Models\AddressResolutionAudit;
use App\Models\User;

final class AddressResolutionAuditLogger
{
    public function log(User $searcher, string $normalizedSolAddress, string $outcome): void
    {
        $pepper = (string) config('app.key');
        $hash = hash_hmac('sha256', 'SOL|'.$normalizedSolAddress, $pepper);

        AddressResolutionAudit::query()->create([
            'searcher_id' => $searcher->id,
            'address_hash' => $hash,
            'outcome' => $outcome,
            'created_at' => now(),
        ]);
    }
}
