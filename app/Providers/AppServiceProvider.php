<?php

namespace App\Providers;

use App\Domain\Chain\Adapters\BitcoinAdapter;
use App\Domain\Chain\Adapters\EthereumAdapter;
use App\Domain\Chain\Adapters\SolanaAdapter;
use App\Domain\Chain\Adapters\TronAdapter;
use App\Domain\Chain\ChainAdapterResolver;
use App\Domain\Observability\Services\OperatorGate;
use App\Listeners\RegisterUserSessionOnLogin;
use App\Listeners\RevokeUserSessionOnLogout;
use App\Models\User;
use App\Rbac\ComparingPermissionResolver;
use Artixcore\ArtGate\Contracts\AuthorizationRepository;
use Artixcore\ArtGate\Contracts\PermissionResolverInterface;
use Artixcore\ArtGate\Contracts\RoleHierarchyProvider;
use Artixcore\ArtGate\Contracts\SuperUserGuard;
use Artixcore\ArtGate\Resolvers\DatabasePermissionResolver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ChainAdapterResolver::class, function ($app) {
            return new ChainAdapterResolver([
                $app->make(EthereumAdapter::class),
                $app->make(BitcoinAdapter::class),
                $app->make(SolanaAdapter::class),
                $app->make(TronAdapter::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! config('vite.use_hot_file')) {
            Vite::useHotFile(storage_path('framework/.vite-no-hot'));
        }

        Event::listen(Login::class, RegisterUserSessionOnLogin::class);
        Event::listen(Logout::class, RevokeUserSessionOnLogout::class);

        $this->registerOperatorGates();
        $this->registerRbacCompareMode();
    }

    /**
     * Map config/observability.php permission names to OperatorGate (is_admin OR ArtGate).
     */
    private function registerOperatorGates(): void
    {
        $this->app->singleton(OperatorGate::class);

        foreach (config('observability.permissions', []) as $key => $permissionName) {
            if (! is_string($permissionName) || $permissionName === '') {
                continue;
            }

            Gate::define($permissionName, static function (User $user) use ($key): bool {
                return app(OperatorGate::class)->allows($user, (string) $key);
            });
        }
    }

    /**
     * RBAC_DRIVER=compare: wrap PermissionResolverInterface to log disagreements between
     * the full ArtGate stack and a raw DatabasePermissionResolver; decisions stay the
     * trusted (decorated) result. Cutover: set RBAC_DRIVER=artgate and rely on
     * ArtGateServiceProvider bindings (or rebind interfaces here if you replace the package).
     */
    private function registerRbacCompareMode(): void
    {
        if (config('rbac.driver') !== 'compare') {
            return;
        }

        $this->app->extend(PermissionResolverInterface::class, function (
            PermissionResolverInterface $resolver,
            Application $app,
        ): PermissionResolverInterface {
            $baseline = new DatabasePermissionResolver(
                $app->make(AuthorizationRepository::class),
                $app->make(SuperUserGuard::class),
                $app->make(RoleHierarchyProvider::class),
            );

            return new ComparingPermissionResolver(
                $resolver,
                $baseline,
                $app->make(LoggerInterface::class),
            );
        });
    }
}
