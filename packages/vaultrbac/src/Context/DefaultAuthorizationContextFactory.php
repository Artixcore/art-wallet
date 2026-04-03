<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Context;

use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\TeamResolver;
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;

final class DefaultAuthorizationContextFactory implements AuthorizationContextFactory
{
    public function __construct(
        private readonly Application $app,
        private readonly TenantResolver $tenantResolver,
        private readonly TeamResolver $teamResolver,
        private readonly ConfigRepository $config,
    ) {}

    public function make(): AuthorizationContext
    {
        $user = null;
        if ($this->app->bound('auth')) {
            $user = $this->app->make('auth')->user();
        }

        $sessionId = null;
        $deviceId = null;

        if (! $this->app->runningInConsole() && $this->app->bound('request')) {
            $request = $this->app->make('request');
            if ($request->hasSession()) {
                $sessionId = $request->session()->getId();
            }
            $header = (string) $this->config->get('vaultrbac.context.device_header', 'X-Device-Id');
            $deviceId = $request->header($header);
        }

        $environment = $this->app->environment();

        return new AuthorizationContext(
            user: $user,
            tenantId: $this->tenantResolver->resolve(),
            teamId: $this->teamResolver->resolve(),
            sessionId: $sessionId,
            deviceId: $deviceId !== '' ? $deviceId : null,
            environment: $environment,
        );
    }
}
