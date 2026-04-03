<?php

namespace App\Providers;

use App\Rbac\ComparingPermissionResolver;
use Artixcore\ArtGate\Contracts\AuthorizationRepository;
use Artixcore\ArtGate\Contracts\PermissionResolverInterface;
use Artixcore\ArtGate\Contracts\RoleHierarchyProvider;
use Artixcore\ArtGate\Contracts\SuperUserGuard;
use Artixcore\ArtGate\Resolvers\DatabasePermissionResolver;
use Illuminate\Contracts\Foundation\Application;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! config('vite.use_hot_file')) {
            Vite::useHotFile(storage_path('framework/.vite-no-hot'));
        }

        $this->registerRbacCompareMode();
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
