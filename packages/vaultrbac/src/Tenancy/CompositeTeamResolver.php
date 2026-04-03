<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tenancy;

use Artwallet\VaultRbac\Contracts\TeamResolver;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class CompositeTeamResolver implements TeamResolver
{
    public function __construct(
        private readonly RequestSourceReader $reader,
        private readonly ConfigRepository $config,
    ) {}

    public function resolve(): string|int|null
    {
        /** @var list<array<string, mixed>> $sources */
        $sources = (array) $this->config->get('vaultrbac.team.sources', []);

        return $this->reader->firstMatch($sources);
    }
}
