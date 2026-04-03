<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Artixcore\ArtGate\Contracts\AuthorizationContextFactory;
use Artixcore\ArtGate\Contracts\AuthorizationRepository;
use Artixcore\ArtGate\Contracts\PermissionResolverInterface;
use Artixcore\ArtGate\Contracts\RoleHierarchyProvider;
use Artixcore\ArtGate\Contracts\SuperUserGuard;
use Artixcore\ArtGate\Models\Permission;
use Artixcore\ArtGate\Models\Role;
use Artixcore\ArtGate\Models\Tenant;
use Artixcore\ArtGate\Resolvers\DatabasePermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matrix-style parity: same context × abilities must match between the container
 * resolver (decorators per config) and a raw DatabasePermissionResolver when caching layers are off.
 */
final class RbacResolverParityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_matches_raw_database_resolver_when_decorator_caching_disabled(): void
    {
        config([
            'artgate.cache.request_memo_enabled' => false,
            'artgate.cache.decisions_enabled' => false,
        ]);

        $tenant = Tenant::query()->create([
            'slug' => 'acme',
            'name' => 'Acme',
            'status' => 'active',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'posts.edit',
            'is_wildcard_parent' => false,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'editor',
            'activation_state' => 'active',
        ]);

        $role->permissions()->attach($permission->id, [
            'tenant_id' => $tenant->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        $user = User::factory()->create();
        $user->assignRole('editor', $tenant->id);

        $this->actingAs($user);
        config(['artgate.default_tenant_id' => $tenant->id]);

        $ctx = app(AuthorizationContextFactory::class)->makeFor($user);

        $trusted = app(PermissionResolverInterface::class);
        $baseline = new DatabasePermissionResolver(
            app(AuthorizationRepository::class),
            app(SuperUserGuard::class),
            app(RoleHierarchyProvider::class),
        );

        foreach (['posts.edit', 'posts.delete', 'nope.missing'] as $ability) {
            $this->assertSame(
                $baseline->authorize($ctx, $ability),
                $trusted->authorize($ctx, $ability),
                "Parity mismatch for ability [{$ability}]",
            );
        }
    }
}
