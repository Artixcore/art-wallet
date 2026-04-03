<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac;

use Artwallet\VaultRbac\Contracts\AuditSink;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\CacheInvalidator;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Contracts\RoleHierarchyProvider;
use Artwallet\VaultRbac\Contracts\SuperUserGuard;
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Artwallet\VaultRbac\Exceptions\ConfigurationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class VaultRbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vaultrbac.php', 'vaultrbac');

        $this->registerBindings();
    }

    public function boot(): void
    {
        if ($this->app->make('config')->get('vaultrbac.run_package_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

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

        $this->bindConcrete($app, TenantResolver::class, $bindings['tenant_resolver'] ?? null);
        $this->bindConcrete($app, AuthorizationContextFactory::class, $bindings['authorization_context_factory'] ?? null);
        $this->bindConcrete($app, PermissionResolverInterface::class, $bindings['permission_resolver'] ?? null);
        $this->bindConcrete($app, RoleHierarchyProvider::class, $bindings['role_hierarchy_provider'] ?? null);
        $this->bindConcrete($app, AuditSink::class, $bindings['audit_sink'] ?? null);
        $this->bindConcrete($app, CacheInvalidator::class, $bindings['cache_invalidator'] ?? null);
        $this->bindConcrete($app, SuperUserGuard::class, $bindings['super_user_guard'] ?? null);

        $app->singleton(VaultRbac::class, static function (Application $app): VaultRbac {
            return new VaultRbac(
                $app->make(PermissionResolverInterface::class),
                $app->make(AuthorizationContextFactory::class),
            );
        });
    }

    /**
     * @param  class-string|null  $implementation
     */
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
