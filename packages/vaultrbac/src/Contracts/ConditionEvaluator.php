<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Context\AuthorizationContext;

/**
 * Evaluates a stored condition expression (typically JSON) for contextual
 * permissions. Implementations are registered by {@see ConditionEvaluator::key()}
 * and must be allowlisted; never eval arbitrary PHP from the database.
 */
interface ConditionEvaluator
{
    public function key(): string;

    /**
     * @param  mixed  $expression  Decoded JSON or structured value from permission_conditions.
     */
    public function evaluate(
        mixed $expression,
        AuthorizationContext $context,
        ?object $resource = null,
    ): bool;
}
