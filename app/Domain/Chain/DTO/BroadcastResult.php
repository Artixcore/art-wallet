<?php

declare(strict_types=1);

namespace App\Domain\Chain\DTO;

final readonly class BroadcastResult
{
    public function __construct(
        public string $txid,
        public bool $alreadyKnown = false,
    ) {}
}
