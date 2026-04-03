<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Support;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Log\LogManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Null-safe request/context helpers for apps (inject or use global helpers when enabled).
 */
final class RequestAuthorization
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly AuthorizationContextFactory $contextFactory,
        private readonly ConfigRepository $config,
        private readonly LogManager $logManager,
    ) {}

    public function currentTenant(): string|int|null
    {
        try {
            return $this->tenantResolver->resolve();
        } catch (Throwable) {
            return null;
        }
    }

    public function currentAuthorizationContext(): AuthorizationContext
    {
        try {
            return $this->contextFactory->make();
        } catch (Throwable $e) {
            if ((bool) $this->config->get('vaultrbac.helpers.strict_context', false)) {
                throw $e;
            }

            $this->logContextFailure($e);

            return new AuthorizationContext(
                user: null,
                tenantId: null,
                teamId: null,
                sessionId: null,
                deviceId: null,
                environment: null,
            );
        }
    }

    public function permissionContextFromRequest(
        Request $request,
        string|int|null $tenantId = null,
        string|int|null $teamId = null,
    ): AuthorizationContext {
        $user = $request->user();
        $base = $this->contextFactory->makeFor($user);

        $ctx = $base;
        if ($tenantId !== null) {
            $ctx = $ctx->withTenant($tenantId);
        }
        if ($teamId !== null) {
            $ctx = $ctx->withTeam($teamId);
        }

        return $ctx;
    }

    public function safeAuthenticatedUser(Request $request): ?Model
    {
        $user = $request->user();

        return $user instanceof Model ? $user : null;
    }

    public function resolveTenantFromRoute(Request $request, string $parameter): string|int|null
    {
        $name = trim($parameter);
        if ($name === '') {
            return null;
        }

        return $this->normalizeRouteValue($request->route($name));
    }

    /**
     * @param  array{tenant?:string,team?:string}  $routeParameterNames
     * @return array{0: string|int|null, 1: string|int|null}
     */
    public function resolveContextFromRoute(Request $request, array $routeParameterNames): array
    {
        $tenantKey = isset($routeParameterNames['tenant']) ? trim((string) $routeParameterNames['tenant']) : '';
        $teamKey = isset($routeParameterNames['team']) ? trim((string) $routeParameterNames['team']) : '';

        $tenantId = $tenantKey !== '' ? $this->resolveTenantFromRoute($request, $tenantKey) : null;
        $teamId = $teamKey !== '' ? $this->resolveTenantFromRoute($request, $teamKey) : null;

        return [$tenantId, $teamId];
    }

    private function normalizeRouteValue(mixed $resolved): string|int|null
    {
        if ($resolved === null) {
            return null;
        }

        if (is_object($resolved) && method_exists($resolved, 'getKey')) {
            $key = $resolved->getKey();

            return is_string($key) || is_int($key) ? $key : null;
        }

        if (is_int($resolved)) {
            return $resolved;
        }

        if (is_string($resolved)) {
            $t = trim($resolved);

            return $t === '' ? null : $t;
        }

        return null;
    }

    private function logContextFailure(Throwable $e): void
    {
        $channel = $this->config->get('vaultrbac.integration.log_channel');
        /** @var LoggerInterface $logger */
        $logger = is_string($channel) && $channel !== ''
            ? $this->logManager->channel($channel)
            : $this->logManager->channel();

        $logger->warning('VaultRBAC authorization context could not be built; returning empty context.', [
            'exception' => $e::class,
        ]);
    }
}
