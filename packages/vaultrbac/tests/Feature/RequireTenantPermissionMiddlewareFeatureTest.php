<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Feature;

use Artwallet\VaultRbac\Http\Middleware\RequireTenantPermissionMiddleware;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * @group security
 */
final class RequireTenantPermissionMiddlewareFeatureTest extends TestCase
{
    use InteractsWithVaultRbac;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', RequireTenantPermissionMiddleware::class.':tid,scoped.act'])
            ->get('/__vrb/tenant/{tid}', fn () => response('ok', 200));
    }

    public function test_route_tenant_parameter_used_for_authorization_context(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 't');
        $perm = $this->createPermissionForTenant($tenant, 'scoped.act');
        $this->attachPermissionToRole($role, $perm, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $this->actingAs($user)
            ->get('/__vrb/tenant/'.$tenant->getKey())
            ->assertOk();
    }

    public function test_wrong_route_tenant_id_denies_even_with_default_tenant(): void
    {
        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $this->withDefaultTenant($tenantA);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenantA, 't');
        $perm = $this->createPermissionForTenant($tenantA, 'scoped.act');
        $this->attachPermissionToRole($role, $perm, $tenantA);
        $this->vault()->assignRole($user, $role, $tenantA->getKey());

        $this->actingAs($user)
            ->get('/__vrb/tenant/'.$tenantB->getKey())
            ->assertStatus(403);
    }
}
