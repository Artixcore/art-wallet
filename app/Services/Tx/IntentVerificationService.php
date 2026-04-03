<?php

declare(strict_types=1);

namespace App\Services\Tx;

use App\Domain\Chain\ChainAdapterResolver;
use App\Models\TransactionIntent;

final class IntentVerificationService
{
    public function __construct(
        private readonly ChainAdapterResolver $adapters,
    ) {}

    public function assertSignedMatchesIntent(TransactionIntent $intent, string $rawSignedHex): void
    {
        $intent->loadMissing(['asset', 'supportedNetwork']);
        $adapter = $this->adapters->forNetwork($intent->supportedNetwork);
        $adapter->assertSignedTxMatchesIntent($intent, $intent->asset, $rawSignedHex);
    }
}
