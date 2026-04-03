<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac;

use Artwallet\VaultRbac\Audit\DatabaseAuditSink;
use Artwallet\VaultRbac\Audit\NullAuditSink;
use Artwallet\VaultRbac\Console\DoctorCommand;
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
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Artwallet\VaultRbac\Database\BlueprintMacros;
use Artwallet\VaultRbac\Events\PermissionGranted;
use Artwallet\VaultRbac\Events\PermissionRevoked;
use Artwallet\VaultRbac\Events\RoleAssigned;
use Artwallet\VaultRbac\Events\RoleRevoked;
use Artwallet\VaultRbac\Exceptions\ConfigurationException;
use Artwallet\VaultRbac\Http\Middleware\AuthorizeVaultPermission;
use Artwallet\VaultRbac\Http\Middleware\EnsureTenantMembership;
use Artwallet\VaultRbac\Http\Middleware\EnsureVaultAnyRole;
use Artwallet\VaultRbac\Http\Middleware\EnsureVaultRole;
use Artwallet\VaultRbac\Http\Middleware\RequireTenantContext;
use Artwallet\VaultRbac\Listeners\RecordVaultRbacAudit;
use Artwallet\VaultRbac\Tenancy\RequestSourceReader;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class VaultRbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vaultrbac.php', 'vaultrbac');

        $this->registerBindings();

        $this->commands([
            DoctorCommand::class,
            SyncPermissionsCommand::class,
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

        $this->registerVaultGate();
        $this->registerVaultBladeDirectives();

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

        $this->bindConcrete($app, TenantResolver::class, $bindings['tenant_resolver'] ?? null);
        $this->bindConcrete($app, TeamResolver::class, $bindings['team_resolver'] ?? null);
        $this->bindConcrete($app, AuthorizationContextFactory::class, $bindings['authorization_context_factory'] ?? null);
        $this->bindConcrete($app, AuthorizationRepository::class, $bindings['authorization_repository'] ?? null);
        $this->bindConcrete($app, PermissionResolverInterface::class, $bindings['permission_resolver'] ?? null);
        $this->bindConcrete($app, RoleHierarchyProvider::class, $bindings['role_hierarchy_provider'] ?? null);
        $this->registerAuditSinkBinding($app, (string) ($bindings['audit_sink'] ?? DatabaseAuditSink::class));
        $this->bindConcrete($app, CacheInvalidator::class, $bindings['cache_invalidator'] ?? null);
        $this->bindConcrete($app, SuperUserGuard::class, $bindings['super_user_guard'] ?? null);
        $this->bindConcrete($app, AssignmentServiceInterface::class, $bindings['assignment_service'] ?? null);
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
            );
        });
    }

    private function registerVaultGate(): void
    {
        $config = $this->app->make('config');

        if (! $config->get('vaultrbac.gate.enabled', true)) {
            return;
        }

        $ability = (string) $config->get('vaultrbac.gate.ability', 'vaultrbac');

        Gate::define($ability, function (?Authenticatable $user, string $permission, mixed $resource = null): bool {
            if (! $user instanceof Model) {
                return false;
            }

            $context = app(AuthorizationContextFactory::class)->makeFor($user);

            return app(PermissionResolverInterface::class)->authorize(
                $context,
                $permission,
                is_object($resource) ? $resource : null,
            );
        });
    }

    private function registerVaultBladeDirectives(): void
    {
        $config = $this->app->make('config');

        if (! $config->get('vaultrbac.blade.enabled', true)) {
            return;
        }

        Blade::if('vaultcan', function (string|\Stringable $ability, mixed $resource = null): bool {
            $object = is_object($resource) ? $resource : null;

            return app(VaultRbac::class)->check($ability, $object);
        });

        Blade::if('vaultrole', function (string|\Stringable $role): bool {
            return app(VaultRbac::class)->hasRole($role);
        });
    }

    /**
     * @param  class-string|null  $implementation
     */
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
