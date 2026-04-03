<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Concerns;

use Artwallet\VaultRbac\VaultRbac as VaultRbacManager;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * For controllers, form requests, or policies that delegate to {@see VaultRbacManager}.
 */
trait AuthorizesWithVault
{
    /**
     * @throws AuthorizationException
     */
    protected function authorizeVault(string|\Stringable $ability, ?object $resource = null): void
    {
        if (! $this->vault()->check($ability, $resource)) {
            throw new AuthorizationException;
        }
    }

    protected function vault(): VaultRbacManager
    {
        return app(VaultRbacManager::class);
    }
}
