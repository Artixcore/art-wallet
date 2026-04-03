<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Feature;

use Artwallet\VaultRbac\Http\Middleware\RequirePermissionMiddleware;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\Route;

final class RequirePermissionMiddlewareFeatureTest extends TestCase
{
    use InteractsWithVaultRbac;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', RequirePermissionMiddleware::class.':gate.perm'])
            ->get('/__vrb/perm', fn () => response('ok', 200));
    }

    public function test_guest_receives_401_when_configured(): void
    {
        $this->get('/__vrb/perm')->assertStatus(401);
    }

    public function test_user_without_permission_receives_403(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $this->createPermissionForTenant($tenant, 'gate.perm');

        $this->actingAs($user)->get('/__vrb/perm')->assertStatus(403);
    }

    public function test_user_with_role_permission_receives_200(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'member');
        $perm = $this->createPermissionForTenant($tenant, 'gate.perm');
        $this->attachPermissionToRole($role, $perm, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $this->actingAs($user)->get('/__vrb/perm')->assertOk()->assertSee('ok');
    }
}
