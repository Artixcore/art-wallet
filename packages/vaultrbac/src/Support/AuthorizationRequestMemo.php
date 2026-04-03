<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Support;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Request-scoped memo for hot authorize() loops. Flush via
 * {@see \Artwallet\VaultRbac\Http\Middleware\FlushAuthorizationRequestMemoMiddleware}
 * or {@see self::flush()} after assignment mutations in the same request.
 */
final class AuthorizationRequestMemo
{
    /** @var array<string, bool> */
    private array $map = [];

    public function remember(
        AuthorizationContext $context,
        string $ability,
        ?object $resource,
        callable $compute,
    ): bool {
        $k = $this->key($context, $ability, $resource);
        if (\array_key_exists($k, $this->map)) {
            return $this->map[$k];
        }

        return $this->map[$k] = $compute();
    }

    public function flush(): void
    {
        $this->map = [];
    }

    private function key(AuthorizationContext $context, string $ability, ?object $resource): string
    {
        $tenant = $context->tenantId !== null ? (string) $context->tenantId : '_';
        $team = $context->teamId !== null ? (string) $context->teamId : '_';
        $uid = $this->subjectKey($context);
        $res = $this->resourceKey($resource);
        $hash = hash('sha256', $ability);

        return $tenant.'|'.$team.'|'.$uid.'|'.$res.'|'.$hash;
    }

    private function subjectKey(AuthorizationContext $context): string
    {
        $user = $context->user;
        if ($user === null) {
            return '_';
        }
        if ($user instanceof Model) {
            return $user->getMorphClass().':'.$user->getKey();
        }

        $id = $user->getAuthIdentifier();

        return is_numeric($id) ? (string) $id : '_';
    }

    private function resourceKey(?object $resource): string
    {
        if ($resource === null) {
            return '0';
        }
        if ($resource instanceof Model) {
            return $resource->getMorphClass().':'.$resource->getKey();
        }

        return 'obj:'.spl_object_id($resource);
    }
}
