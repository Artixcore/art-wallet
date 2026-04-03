<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Log\LogManager;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Fail-closed HTTP integration: consistent abort codes, logging, no silent bypass.
 */
final class IntegrationAuthorization
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logManager,
    ) {}

    public function assertAuthenticatedOrAbort(Request $request): void
    {
        if ($request->user() !== null) {
            return;
        }

        $treatAsUnauth = (bool) $this->config->get('vaultrbac.middleware.treat_guest_as_unauthorized', true);
        $status = $treatAsUnauth
            ? (int) $this->config->get('vaultrbac.middleware.unauthenticated_status', 401)
            : (int) $this->config->get('vaultrbac.middleware.unauthorized_status', 403);

        abort($status);
    }

    /**
     * @param  list<string>  $abilities
     */
    public function abortIfInvalidAbilities(array $abilities, string $message = 'Invalid permission arguments.'): void
    {
        if ($abilities !== []) {
            return;
        }

        $this->logWarning($message);

        abort((int) $this->config->get('vaultrbac.middleware.invalid_arguments_status', 403));
    }

    /**
     * @param  list<string>  $roles
     */
    public function abortIfInvalidRoles(array $roles, string $message = 'Invalid role arguments.'): void
    {
        if ($roles !== []) {
            return;
        }

        $this->logWarning($message);

        abort((int) $this->config->get('vaultrbac.middleware.invalid_arguments_status', 403));
    }

    public function abortIfInvalidArgument(bool $valid, string $message): void
    {
        if ($valid) {
            return;
        }

        $this->logWarning($message);

        abort((int) $this->config->get('vaultrbac.middleware.invalid_arguments_status', 403));
    }

    public function abortIntegrationFailure(Throwable $e): never
    {
        $channel = $this->config->get('vaultrbac.integration.log_channel');
        /** @var LoggerInterface $logger */
        $logger = is_string($channel) && $channel !== ''
            ? $this->logManager->channel($channel)
            : $this->logManager->channel();

        $message = 'VaultRBAC integration aborted due to internal error.';

        $context = [
            'exception' => $e::class,
        ];

        if ($this->config->get('app.debug')) {
            $context['file'] = $e->getFile();
            $context['line'] = $e->getLine();
        }

        $logger->error($message, $context);

        abort((int) $this->config->get('vaultrbac.middleware.integration_error_status', 403));
    }

    public function missingPermissionStatus(): int
    {
        return (int) $this->config->get('vaultrbac.middleware.missing_permission_status', 403);
    }

    private function logWarning(string $message): void
    {
        $channel = $this->config->get('vaultrbac.integration.log_channel');
        /** @var LoggerInterface $logger */
        $logger = is_string($channel) && $channel !== ''
            ? $this->logManager->channel($channel)
            : $this->logManager->channel();

        $logger->warning($message);
    }
}
