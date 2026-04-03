<?php

declare(strict_types=1);

namespace App\Rbac;

use Artixcore\ArtGate\Context\AuthorizationContext;
use Artixcore\ArtGate\Contracts\PermissionResolverInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Logs when two resolvers disagree; returns the trusted (production) decision.
 * Use with RBAC_DRIVER=compare on staging to detect cache / decorator drift.
 */
final class ComparingPermissionResolver implements PermissionResolverInterface
{
    public function __construct(
        private readonly PermissionResolverInterface $trusted,
        private readonly PermissionResolverInterface $baseline,
        private readonly LoggerInterface $logger,
    ) {}

    public function authorize(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        $trusted = $this->trusted->authorize($context, $ability, $resource);

        try {
            $baseline = $this->baseline->authorize($context, $ability, $resource);
        } catch (Throwable $e) {
            $this->logger->error('ArtGate compare: baseline resolver threw', [
                'ability' => (string) $ability,
                'exception' => $e->getMessage(),
            ]);

            return $trusted;
        }

        if ($trusted !== $baseline) {
            $this->logger->warning('ArtGate compare: permission decision mismatch', [
                'ability' => (string) $ability,
                'trusted' => $trusted,
                'baseline' => $baseline,
                'tenant_id' => $context->tenantId,
                'team_id' => $context->teamId,
                'user_id' => $context->user?->getAuthIdentifier(),
            ]);
        }

        return $trusted;
    }
}
