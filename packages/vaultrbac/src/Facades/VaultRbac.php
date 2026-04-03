<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool check(string|\Stringable $ability, ?object $resource = null)
 * @method static bool checkFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, string|\Stringable $ability, ?object $resource = null)
 *
 * @see \Artwallet\VaultRbac\VaultRbac
 */
class VaultRbac extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Artwallet\VaultRbac\VaultRbac::class;
    }
}
