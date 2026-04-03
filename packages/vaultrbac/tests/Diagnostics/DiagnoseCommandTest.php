<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Diagnostics;

use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\RoleHierarchy;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @group diagnostics
 */
final class DiagnoseCommandTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_diagnose_json_reports_schema_ok_on_clean_install(): void
    {
        $buffer = new BufferedOutput;
        $exit = Artisan::call('vaultrbac:diagnose', ['--json' => true], $buffer);
        self::assertSame(0, $exit);
        $raw = $buffer->fetch();
        self::assertNotSame('', $raw);
        $lines = array_values(array_filter(array_map(trim(...), preg_split('/\R/u', $raw) ?: [])));
        $jsonLine = (string) end($lines);
        self::assertStringStartsWith('{', $jsonLine);
        $payload = json_decode($jsonLine, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('ok', $payload);
        self::assertArrayHasKey('checks', $payload);
    }

    public function test_diagnose_fails_when_role_hierarchy_has_cycle(): void
    {
        $tenant = $this->createTenant();

        $a = Role::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'c-a-'.uniqid(),
            'activation_state' => 'active',
        ]);
        $b = Role::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'c-b-'.uniqid(),
            'activation_state' => 'active',
        ]);

        RoleHierarchy::query()->create([
            'tenant_id' => $tenant->getKey(),
            'child_role_id' => $a->getKey(),
            'parent_role_id' => $b->getKey(),
        ]);
        RoleHierarchy::query()->create([
            'tenant_id' => $tenant->getKey(),
            'child_role_id' => $b->getKey(),
            'parent_role_id' => $a->getKey(),
        ]);

        $exit = Artisan::call('vaultrbac:diagnose', ['--tenant' => (string) $tenant->getKey()]);
        self::assertNotSame(0, $exit);
    }

    public function test_diagnose_fails_when_orphan_model_roles_exist(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Inserting a true orphan model_roles row requires FK-off semantics not reliably available under RefreshDatabase/SQLite; run against MySQL in CI if needed.');
        }

        $tenant = $this->createTenant();
        $user = $this->createUser();

        $table = (string) config('vaultrbac.tables.model_roles');
        Schema::withoutForeignKeyConstraints(function () use ($table, $tenant, $user): void {
            DB::table($table)->insert([
                'tenant_id' => $tenant->getKey(),
                'team_id' => null,
                'team_key' => 0,
                'role_id' => 999999999,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->getKey(),
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $exit = Artisan::call('vaultrbac:diagnose');
        self::assertNotSame(0, $exit);
    }

    public function test_diagnose_detects_cache_warm_snapshot_drift(): void
    {
        config([
            'vaultrbac.cache.prefix' => 'vrb_diag',
            'cache.default' => 'array',
        ]);

        $tenant = $this->createTenant();
        $scope = (string) config('vaultrbac.freshness.scope');

        /** @var \Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository $versions */
        $versions = $this->app->make(\Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository::class);
        $versions->bump($tenant->getKey(), $scope);

        $prefix = (string) config('vaultrbac.cache.prefix');
        $warmKey = $prefix.':warm:tenant:'.(string) $tenant->getKey().':'.$scope;
        cache()->put($warmKey, 0, 60);

        $exit = Artisan::call('vaultrbac:diagnose', ['--tenant' => (string) $tenant->getKey()]);
        self::assertNotSame(0, $exit);
    }
}
