<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac;

use Artwallet\VaultRbac\Audit\DatabaseAuditSink;
use Artwallet\VaultRbac\Audit\NullAuditSink;
use Artwallet\VaultRbac\Console\ApprovalApproveCommand;
use Artwallet\VaultRbac\Console\ApprovalListCommand;
use Artwallet\VaultRbac\Console\ApprovalRejectCommand;
use Artwallet\VaultRbac\Console\AuditPruneCommand;
use Artwallet\VaultRbac\Console\CacheFlushCommand;
use Artwallet\VaultRbac\Console\CacheWarmCommand;
use Artwallet\VaultRbac\Console\DiagnoseCommand;
use Artwallet\VaultRbac\Console\DoctorCommand;
use Artwallet\VaultRbac\Console\PermissionAssignCommand;
use Artwallet\VaultRbac\Console\PermissionCreateCommand;
use Artwallet\VaultRbac\Console\RoleAssignCommand;
use Artwallet\VaultRbac\Console\RoleCreateCommand;
use Artwallet\VaultRbac\Console\SyncPermissionsCommand;
use Artwallet\VaultRbac\Contracts\ApprovalRequestRepository;
use Artwallet\VaultRbac\Contracts\ApprovalWorkflowInterface;
use Artwallet\VaultRbac\Contracts\AssignmentServiceInterface;
use Artwallet\VaultRbac\Contracts\AuditLogRepository;
use Artwallet\VaultRbac\Contracts\AuditSink;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\AuthorizationRepository;
use Artwallet\VaultRbac\Contracts\CacheInvalidator;
use Artwallet\VaultRbac\Contracts\EncryptedMetadataRepository;
use Artwallet\VaultRbac\Contracts\HierarchyRepository;
use Artwallet\VaultRbac\Contracts\PermissionAssignmentRepository;
use Artwallet\VaultRbac\Contracts\PermissionCacheAdminInterface;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Contracts\RoleAssignmentRepository;
use Artwallet\VaultRbac\Contracts\RoleHierarchyProvider;
use Artwallet\VaultRbac\Contracts\RoleRepository;
use Artwallet\VaultRbac\Contracts\SuperUserGuard;
use Artwallet\VaultRbac\Contracts\TeamResolver;
use Artwallet\VaultRbac\Contracts\TenantMembershipVerifier;
use Artwallet\VaultRbac\Contracts\TenantRepository;
use Artwallet\VaultRbac\Contracts\TemporaryGrantServiceInterface;
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Artwallet\VaultRbac\Database\BlueprintMacros;
use Artwallet\VaultRbac\Events\PermissionGranted;
use Artwallet\VaultRbac\Events\PermissionRevoked;
use Artwallet\VaultRbac\Events\RoleAssigned;
use Artwallet\VaultRbac\Events\RoleRevoked;
use Artwallet\VaultRbac\Exceptions\ConfigurationException;
use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Artwallet\VaultRbac\Http\Middleware\AuthorizeVaultPermission;
use Artwallet\VaultRbac\Http\Middleware\EnforcePermissionFreshnessMiddleware;
use Artwallet\VaultRbac\Http\Middleware\EnsureTenantMembership;
use Artwallet\VaultRbac\Http\Middleware\EnsureVaultAnyRole;
use Artwallet\VaultRbac\Http\Middleware\EnsureVaultRole;
use Artwallet\VaultRbac\Http\Middleware\RequireAllPermissionsMiddleware;
use Artwallet\VaultRbac\Http\Middleware\RequireAnyPermissionMiddleware;
use Artwallet\VaultRbac\Http\Middleware\RequireAnyRoleMiddleware;
use Artwallet\VaultRbac\Http\Middleware\RequireApprovedPrivilegeMiddleware;
use Artwallet\VaultRbac\Http\Middleware\RequireContextPermissionMiddleware;
use Artwallet\VaultRbac\Http\Middleware\RequirePermissionMiddleware;
use Artwallet\VaultRbac\Http\Middleware\RequireRoleMiddleware;
use Artwallet\VaultRbac\Http\Middleware\RequireTenantContext;
use Artwallet\VaultRbac\Http\Middleware\RequireTenantPermissionMiddleware;
use Artwallet\VaultRbac\Http\Middleware\ResolveTenantContextMiddleware;
use Artwallet\VaultRbac\Listeners\RecordVaultRbacAudit;
use Artwallet\VaultRbac\Resolvers\SafePermissionResolver;
use Artwallet\VaultRbac\Support\RequestAuthorization;
use Artwallet\VaultRbac\Tenancy\RequestSourceReader;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

final class VaultRbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vaultrbac.php', 'vaultrbac');

        $this->registerBindings();

        $this->commands([
            DiagnoseCommand::class,
            DoctorCommand::class,
            SyncPermissionsCommand::class,
            CacheWarmCommand::class,
            CacheFlushCommand::class,
            AuditPruneCommand::class,
            ApprovalListCommand::class,
            ApprovalApproveCommand::class,
            ApprovalRejectCommand::class,
            RoleCreateCommand::class,
            PermissionCreateCommand::class,
            RoleAssignCommand::class,
            PermissionAssignCommand::class,
        ]);
    }

    public function boot(): void
    {
        BlueprintMacros::register();

        if ($this->app->make('config')->get('vaultrbac.run_package_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('vault.tenant', RequireTenantContext::class);
        $router->aliasMiddleware('vault.tenant.member', EnsureTenantMembership::class);
        $router->aliasMiddleware('vault.permission', AuthorizeVaultPermission::class);
        $router->aliasMiddleware('vault.role', EnsureVaultRole::class);
        $router->aliasMiddleware('vault.any-role', EnsureVaultAnyRole::class);

        $router->aliasMiddleware('vrb.permission', RequirePermissionMiddleware::class);
        $router->aliasMiddleware('vrb.permission.any', RequireAnyPermissionMiddleware::class);
        $router->aliasMiddleware('vrb.permission.all', RequireAllPermissionsMiddleware::class);
        $router->aliasMiddleware('vrb.role', RequireRoleMiddleware::class);
        $router->aliasMiddleware('vrb.role.any', RequireAnyRoleMiddleware::class);
        $router->aliasMiddleware('vrb.tenant.permission', RequireTenantPermissionMiddleware::class);
        $router->aliasMiddleware('vrb.context.permission', RequireContextPermissionMiddleware::class);
        $router->aliasMiddleware('vrb.approved.privilege', RequireApprovedPrivilegeMiddleware::class);
        $router->aliasMiddleware('vrb.tenant.resolve', ResolveTenantContextMiddleware::class);
        $router->aliasMiddleware('vrb.permission.freshness', EnforcePermissionFreshnessMiddleware::class);

        $this->loadHelpersIfEnabled();

        $deferHttp = (bool) $this->app->make('config')->get('vaultrbac.integration.defer_http_features_in_console', false)
            && $this->app->runningInConsole();

        if (! $deferHttp) {
            $this->registerVaultGate();
            $this->registerVaultBladeDirectives();
        }

        $listener = RecordVaultRbacAudit::class;
        Event::listen(RoleAssigned::class, [$listener, 'handleRoleAssigned']);
        Event::listen(RoleRevoked::class, [$listener, 'handleRoleRevoked']);
        Event::listen(PermissionGranted::class, [$listener, 'handlePermissionGranted']);
        Event::listen(PermissionRevoked::class, [$listener, 'handlePermissionRevoked']);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/vaultrbac.php' => $this->app->configPath('vaultrbac.php'),
            ], 'vaultrbac-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'vaultrbac-migrations');

            $this->publishes([
                __DIR__.'/../resources/stubs' => $this->app->basePath('stubs/vaultrbac'),
            ], 'vaultrbac-stubs');
        }
    }

    private function registerBindings(): void
    {
        $app = $this->app;

        $bindings = (array) $app->make('config')->get('vaultrbac.bindings', []);

        $app->singleton(RequestSourceReader::class);
        $app->singleton(IntegrationAuthorization::class);
        $app->singleton(RequestAuthorization::class);

        $this->bindConcrete($app, TenantResolver::class, $bindings['tenant_resolver'] ?? null);
        $this->bindConcrete($app, TeamResolver::class, $bindings['team_resolver'] ?? null);
        $this->bindConcrete($app, AuthorizationContextFactory::class, $bindings['authorization_context_factory'] ?? null);
        $this->bindConcrete($app, AuthorizationRepository::class, $bindings['authorization_repository'] ?? null);
        $this->registerPermissionResolverBinding($app, $bindings['permission_resolver'] ?? null);
        $this->bindConcrete($app, RoleHierarchyProvider::class, $bindings['role_hierarchy_provider'] ?? null);
        $this->registerAuditSinkBinding($app, (string) ($bindings['audit_sink'] ?? DatabaseAuditSink::class));
        $this->bindConcrete($app, CacheInvalidator::class, $bindings['cache_invalidator'] ?? null);
        $this->bindConcrete($app, SuperUserGuard::class, $bindings['super_user_guard'] ?? null);
        $this->bindConcrete($app, AssignmentServiceInterface::class, $bindings['assignment_service'] ?? null);
        $this->registerPermissionCacheAdminBinding($app, (string) ($bindings['permission_cache_admin'] ?? \Artwallet\VaultRbac\Services\PermissionCacheAdminService::class));
        $this->bindConcrete($app, TemporaryGrantServiceInterface::class, $bindings['temporary_grant_service'] ?? null);
        $this->bindConcrete($app, ApprovalWorkflowInterface::class, $bindings['approval_workflow'] ?? null);
        $this->bindConcrete($app, TenantMembershipVerifier::class, $bindings['tenant_membership_verifier'] ?? null);
        $this->bindConcrete($app, RoleRepository::class, $bindings['role_repository'] ?? null);
        $this->bindConcrete($app, PermissionRepository::class, $bindings['permission_repository'] ?? null);
        $this->bindConcrete($app, TenantRepository::class, $bindings['tenant_repository'] ?? null);
        $this->bindConcrete($app, ApprovalRequestRepository::class, $bindings['approval_request_repository'] ?? null);
        $this->bindConcrete($app, AuditLogRepository::class, $bindings['audit_log_repository'] ?? null);
        $this->bindConcrete($app, EncryptedMetadataRepository::class, $bindings['encrypted_metadata_repository'] ?? null);
        $this->bindConcrete($app, PermissionCacheVersionRepository::class, $bindings['permission_cache_version_repository'] ?? null);
        $this->bindConcrete($app, RoleAssignmentRepository::class, $bindings['role_assignment_repository'] ?? null);
        $this->bindConcrete($app, PermissionAssignmentRepository::class, $bindings['permission_assignment_repository'] ?? null);
        $this->bindConcrete($app, HierarchyRepository::class, $bindings['hierarchy_repository'] ?? null);

        $app->singleton(VaultRbac::class, static function (Application $app): VaultRbac {
            return new VaultRbac(
                $app->make(PermissionResolverInterface::class),
                $app->make(AuthorizationContextFactory::class),
                $app->make(AssignmentServiceInterface::class),
                $app->make(AuthorizationRepository::class),
                $app->make(ConfigRepository::class),
                $app->make(ApprovalWorkflowInterface::class),
                $app->make(AuditLogRepository::class),
                $app->make(PermissionCacheAdminInterface::class),
                $app->make(TemporaryGrantServiceInterface::class),
                $app->make(PermissionCacheVersionRepository::class),
            );
        });
    }

    private function registerPermissionCacheAdminBinding(Application $app, string $implementation): void
    {
        if ($implementation === '') {
            throw new ConfigurationException(
                'vaultrbac.bindings mapping for [Artwallet\\VaultRbac\\Contracts\\PermissionCacheAdminInterface] is missing or empty.',
            );
        }

        $app->singleton(PermissionCacheAdminInterface::class, static function (Application $app) use ($implementation): PermissionCacheAdminInterface {
            $instance = $app->make($implementation);
            if (! $instance instanceof PermissionCacheAdminInterface) {
                throw new ConfigurationException(
                    sprintf('vaultrbac.bindings.permission_cache_admin must implement PermissionCacheAdminInterface, [%s] given.', $implementation),
                );
            }

            return $instance;
        });

        $app->when($implementation)
            ->needs(\Illuminate\Contracts\Cache\Repository::class)
            ->give(static function (Application $app): \Illuminate\Contracts\Cache\Repository {
                $store = $app->make('config')->get('vaultrbac.cache.store');

                /** @var CacheFactory $factory */
                $factory = $app->make(CacheFactory::class);

                return ($store !== null && $store !== '')
                    ? $factory->store((string) $store)
                    : $factory->store();
            });
    }

    private function registerVaultGate(): void
    {
        $config = $this->app->make('config');

        if (! $config->get('vaultrbac.gate.enabled', true)) {
            return;
        }

        $ability = (string) $config->get('vaultrbac.gate.ability', 'vaultrbac');

        Gate::define($ability, function (?Authenticatable $user, mixed $permission, mixed $resource = null): bool {
            if (! $user instanceof Model) {
                return false;
            }

            $name = self::normalizeGatePermissionName($permission);
            if ($name === null) {
                return false;
            }

            $context = app(AuthorizationContextFactory::class)->makeFor($user);

            return app(PermissionResolverInterface::class)->authorize(
                $context,
                $name,
                is_object($resource) ? $resource : null,
            );
        });

        if ($config->get('vaultrbac.gate.register_any_ability', true)) {
            $anyAbility = (string) $config->get('vaultrbac.gate.ability_any', 'vaultrbac.any');
            Gate::define($anyAbility, function (?Authenticatable $user, mixed ...$args): bool {
                if (! $user instanceof Model) {
                    return false;
                }

                $abilities = self::flattenGatePermissionArguments($args);
                if ($abilities === []) {
                    return false;
                }

                $context = app(AuthorizationContextFactory::class)->makeFor($user);
                $resolver = app(PermissionResolverInterface::class);
                foreach ($abilities as $name) {
                    if ($resolver->authorize($context, $name)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if ($config->get('vaultrbac.gate.register_all_ability', true)) {
            $allAbility = (string) $config->get('vaultrbac.gate.ability_all', 'vaultrbac.all');
            Gate::define($allAbility, function (?Authenticatable $user, mixed ...$args): bool {
                if (! $user instanceof Model) {
                    return false;
                }

                $abilities = self::flattenGatePermissionArguments($args);
                if ($abilities === []) {
                    return false;
                }

                $context = app(AuthorizationContextFactory::class)->makeFor($user);
                $resolver = app(PermissionResolverInterface::class);
                foreach ($abilities as $name) {
                    if (! $resolver->authorize($context, $name)) {
                        return false;
                    }
                }

                return true;
            });
        }
    }

    /**
     * @param  list<mixed>  $args
     * @return list<string>
     */
    private static function flattenGatePermissionArguments(array $args): array
    {
        $out = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $item) {
                    $n = self::normalizeGatePermissionName($item);
                    if ($n !== null) {
                        $out[] = $n;
                    }
                }

                continue;
            }

            $n = self::normalizeGatePermissionName($arg);
            if ($n !== null) {
                $out[] = $n;
            }
        }

        return $out;
    }

    private static function normalizeGatePermissionName(mixed $permission): ?string
    {
        if ($permission instanceof \Stringable) {
            $permission = (string) $permission;
        }

        if (! is_string($permission)) {
            return null;
        }

        $t = trim($permission);

        return $t === '' ? null : $t;
    }

    private function registerVaultBladeDirectives(): void
    {
        $config = $this->app->make('config');

        if (! $config->get('vaultrbac.blade.enabled', true)) {
            return;
        }

        Blade::if('vaultcan', function (mixed $ability = null, mixed $resource = null): bool {
            try {
                if (! is_string($ability) && ! $ability instanceof \Stringable) {
                    return false;
                }

                $object = is_object($resource) ? $resource : null;

                return app(VaultRbac::class)->check($ability, $object);
            } catch (Throwable) {
                return false;
            }
        });

        Blade::if('vaultrole', function (mixed $role = null): bool {
            try {
                if (! is_string($role) && ! $role instanceof \Stringable) {
                    return false;
                }

                return app(VaultRbac::class)->hasRole($role);
            } catch (Throwable) {
                return false;
            }
        });

        if (! $config->get('vaultrbac.blade.extended', true)) {
            return;
        }

        Blade::if('vaultcanany', function (mixed $abilities = null, mixed $resource = null): bool {
            try {
                $list = self::normalizeBladeAbilityList($abilities);
                if ($list === []) {
                    return false;
                }
                $object = is_object($resource) ? $resource : null;

                return app(VaultRbac::class)->checkAny($list, $object);
            } catch (Throwable) {
                return false;
            }
        });

        Blade::if('vaultcanall', function (mixed $abilities = null, mixed $resource = null): bool {
            try {
                $list = self::normalizeBladeAbilityList($abilities);
                if ($list === []) {
                    return false;
                }
                $object = is_object($resource) ? $resource : null;

                return app(VaultRbac::class)->checkAll($list, $object);
            } catch (Throwable) {
                return false;
            }
        });

        Blade::if('vaultroleany', function (mixed $roles = null): bool {
            try {
                if (! is_string($roles) && ! $roles instanceof \Stringable) {
                    return false;
                }
                $names = array_values(array_filter(array_map(trim(...), explode('|', (string) $roles))));
                $vault = app(VaultRbac::class);
                foreach ($names as $role) {
                    if ($vault->hasRole($role)) {
                        return true;
                    }
                }

                return false;
            } catch (Throwable) {
                return false;
            }
        });

        Blade::if('vaultcantenant', function (
            mixed $tenantId = null,
            mixed $ability = null,
            mixed $resource = null,
        ): bool {
            try {
                if (($tenantId !== null && ! is_string($tenantId) && ! is_int($tenantId))
                    || (! is_string($ability) && ! $ability instanceof \Stringable)) {
                    return false;
                }

                $user = request()->user();
                if (! $user instanceof Model) {
                    return false;
                }

                $ctx = app(AuthorizationContextFactory::class)->makeFor($user)->withTenant($tenantId);

                return app(PermissionResolverInterface::class)->authorize(
                    $ctx,
                    $ability,
                    is_object($resource) ? $resource : null,
                );
            } catch (Throwable) {
                return false;
            }
        });

        Blade::if('vaultcanteam', function (
            mixed $tenantId = null,
            mixed $teamId = null,
            mixed $ability = null,
            mixed $resource = null,
        ): bool {
            try {
                if (($tenantId !== null && ! is_string($tenantId) && ! is_int($tenantId))
                    || ($teamId !== null && ! is_string($teamId) && ! is_int($teamId))
                    || (! is_string($ability) && ! $ability instanceof \Stringable)) {
                    return false;
                }

                $user = request()->user();
                if (! $user instanceof Model) {
                    return false;
                }

                $ctx = app(AuthorizationContextFactory::class)->makeFor($user)
                    ->withTenant($tenantId)
                    ->withTeam($teamId);

                return app(PermissionResolverInterface::class)->authorize(
                    $ctx,
                    $ability,
                    is_object($resource) ? $resource : null,
                );
            } catch (Throwable) {
                return false;
            }
        });
    }

    /**
     * @return list<string>
     */
    private static function normalizeBladeAbilityList(mixed $abilities): array
    {
        if (is_string($abilities) || $abilities instanceof \Stringable) {
            $parts = array_map(trim(...), explode(',', (string) $abilities));

            return array_values(array_filter($parts, static fn (string $p): bool => $p !== ''));
        }

        if (! is_array($abilities)) {
            return [];
        }

        $out = [];
        foreach ($abilities as $item) {
            if (is_string($item) || $item instanceof \Stringable) {
                $t = trim((string) $item);
                if ($t !== '') {
                    $out[] = $t;
                }
            }
        }

        return $out;
    }

    private function loadHelpersIfEnabled(): void
    {
        $config = $this->app->make('config');

        if (! $config->get('vaultrbac.helpers.enabled', false)) {
            return;
        }

        $path = __DIR__.'/Support/helpers.php';
        if (is_file($path)) {
            require_once $path;
        }

        if ($config->get('vaultrbac.helpers.rbac_aliases_enabled', false)) {
            $aliases = __DIR__.'/Support/helpers_rbac_aliases.php';
            if (is_file($aliases)) {
                require_once $aliases;
            }
        }
    }

    /**
     * @param  class-string|null  $implementation
     */
    private function registerPermissionResolverBinding(Application $app, ?string $implementation): void
    {
        if ($implementation === null || $implementation === '') {
            throw new ConfigurationException(
                'vaultrbac.bindings mapping for [Artwallet\\VaultRbac\\Contracts\\PermissionResolverInterface] is missing or empty.',
            );
        }

        $app->singleton(PermissionResolverInterface::class, static function (Application $app) use ($implementation): PermissionResolverInterface {
            $inner = $app->make($implementation);
            if (! $inner instanceof PermissionResolverInterface) {
                throw new ConfigurationException(
                    sprintf('vaultrbac.bindings.permission_resolver must implement PermissionResolverInterface, [%s] given.', $implementation),
                );
            }

            $config = $app->make(ConfigRepository::class);
            if ($config->get('vaultrbac.integration.safe_resolver', true)) {
                return new SafePermissionResolver(
                    $inner,
                    $config,
                    $app->make(LogManager::class),
                );
            }

            return $inner;
        });
    }

    private function registerAuditSinkBinding(Application $app, string $implementation): void
    {
        $app->singleton(AuditSink::class, static function (Application $app) use ($implementation): AuditSink {
            if (! $app->make('config')->get('vaultrbac.audit.enabled', true)) {
                return new NullAuditSink;
            }

            return $app->make($implementation);
        });
    }

    private function bindConcrete(Application $app, string $abstract, ?string $implementation): void
    {
        if ($implementation === null || $implementation === '') {
            throw new ConfigurationException(
                sprintf('vaultrbac.bindings mapping for [%s] is missing or empty.', $abstract),
            );
        }

        $app->singleton($abstract, static fn (Application $app): object => $app->make($implementation));
    }
}
