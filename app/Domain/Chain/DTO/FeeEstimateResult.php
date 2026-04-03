<?php

declare(strict_types=1);

namespace App\Domain\Chain\DTO;

final readonly class FeeEstimateResult
{
    /**
     * @param  array<string, mixed>  $tiers  e.g. slow, standard, fast with chain-specific fields
     */
    public function __construct(
        public array $tiers,
        public int $expiresAtEpoch,
    ) {}
}
