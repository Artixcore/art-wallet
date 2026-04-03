<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;

/**
 * Application-facing entry for authorization checks (Facade root).
 */
final class VaultRbac
{
    public function __construct(
        private readonly PermissionResolverInterface $resolver,
        private readonly AuthorizationContextFactory $contextFactory,
    ) {}

    public function check(string|\Stringable $ability, ?object $resource = null): bool
    {
        return $this->resolver->authorize(
            $this->contextFactory->make(),
            $ability,
            $resource,
        );
    }

    /**
     * Authorize using an explicit context (queue jobs, tests, sub-requests).
     */
    public function checkFor(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return $this->resolver->authorize($context, $ability, $resource);
    }
}
