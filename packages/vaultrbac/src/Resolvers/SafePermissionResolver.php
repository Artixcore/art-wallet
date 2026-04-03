<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Resolvers;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Logging\Factory as LogFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Decorates the configured resolver: any Throwable becomes log + deny (fail-closed).
 */
final class SafePermissionResolver implements PermissionResolverInterface
{
    public function __construct(
        private readonly PermissionResolverInterface $inner,
        private readonly ConfigRepository $config,
        private readonly LogFactory $logFactory,
    ) {}

    public function authorize(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        try {
            return $this->inner->authorize($context, $ability, $resource);
        } catch (Throwable $e) {
            $this->logDeny($e);

            return false;
        }
    }

    private function logDeny(Throwable $e): void
    {
        $channel = $this->config->get('vaultrbac.integration.log_channel');
        $level = (string) $this->config->get('vaultrbac.integration.log_level', 'error');

        /** @var LoggerInterface $logger */
        $logger = is_string($channel) && $channel !== ''
            ? $this->logFactory->channel($channel)
            : $this->logFactory->channel();

        $message = 'VaultRBAC permission resolver denied due to internal error.';

        if ($this->config->get('vaultrbac.integration.expose_integration_exceptions', false)
            && function_exists('app') && app()->environment('local')) {
            $message .= ' '.$e->getMessage();
        }

        $context = [
            'exception' => $e::class,
        ];

        if ($this->config->get('app.debug')) {
            $context['file'] = $e->getFile();
            $context['line'] = $e->getLine();
        }

        match ($level) {
            'warning', 'warn' => $logger->warning($message, $context),
            'notice' => $logger->notice($message, $context),
            'info' => $logger->info($message, $context),
            'debug' => $logger->debug($message, $context),
            default => $logger->error($message, $context),
        };
    }
}
