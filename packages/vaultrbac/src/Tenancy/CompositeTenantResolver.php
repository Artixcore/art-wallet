<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tenancy;

use Artwallet\VaultRbac\Contracts\TenantResolver;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class CompositeTenantResolver implements TenantResolver
{
    public function __construct(
        private readonly RequestSourceReader $reader,
        private readonly ConfigRepository $config,
    ) {}

    public function resolve(): string|int|null
    {
        /** @var list<array<string, mixed>> $sources */
        $sources = (array) $this->config->get('vaultrbac.tenant.sources', []);

        return $this->reader->firstMatch($sources);
    }
}
