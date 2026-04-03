<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Contracts\HierarchyRepository;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Database\VaultrbacTables;
use Artwallet\VaultRbac\Models\EncryptedMetadata;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DiagnoseCommand extends Command
{
    protected $signature = 'vaultrbac:diagnose
                            {--tenant= : Limit hierarchy/orphan checks to a tenant id}
                            {--json : Machine-readable output}';

    protected $description = 'Deep health check: config, schema, hierarchy, orphans, cache, metadata';

    public function handle(
        ConfigRepository $config,
        HierarchyRepository $hierarchy,
        PermissionCacheVersionRepository $versions,
        CacheFactory $cacheFactory,
    ): int {
        $ok = true;
        $lines = [];

        $tenantFilter = $this->option('tenant');

        try {
            VaultrbacTables::name('tenants');
        } catch (Throwable $e) {
            $this->lineFail('Invalid table map: '.$e->getMessage());
            $ok = false;
            $lines[] = ['ok' => false, 'check' => 'table_map', 'detail' => $e->getMessage()];
        }

        $tables = (array) $config->get('vaultrbac.tables', []);
        foreach ($tables as $key => $table) {
            if (! is_string($table) || $table === '') {
                $this->warn("Table key [{$key}] is empty.");
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'table_config', 'key' => $key];

                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->lineFail("Missing table [{$table}] (vaultrbac.tables.{$key}).");
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'schema', 'table' => $table];
            } else {
                $this->lineOk("schema: {$table}");
                $lines[] = ['ok' => true, 'check' => 'schema', 'table' => $table];
            }
        }

        $appKey = $config->get('app.key');
        if (! is_string($appKey) || $appKey === '') {
            $this->lineFail('APP_KEY is not set.');
            $ok = false;
            $lines[] = ['ok' => false, 'check' => 'app_key'];
        }

        if ($config->get('vaultrbac.audit.enabled') && ! is_string($config->get('vaultrbac.audit.secret')) && (! is_string($appKey) || $appKey === '')) {
            $this->warn('Audit enabled but vaultrbac.audit.secret empty; ensure APP_KEY is set.');
            $lines[] = ['ok' => true, 'check' => 'audit_secret', 'warn' => true];
        }

        $bindings = (array) $config->get('vaultrbac.bindings', []);
        foreach ([
            'permission_resolver',
            'assignment_service',
            'authorization_context_factory',
        ] as $required) {
            if (! isset($bindings[$required]) || ! is_string($bindings[$required]) || $bindings[$required] === '') {
                $this->lineFail("Missing vaultrbac.bindings.{$required}.");
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'bindings', 'key' => $required];
            }
        }

        try {
            $store = $cacheFactory->store();
            $store->put('vaultrbac:diagnose:probe', '1', 5);
            $probe = $store->get('vaultrbac:diagnose:probe');
            $store->forget('vaultrbac:diagnose:probe');
            if ($probe !== '1') {
                $this->lineFail('Default cache store read/write probe failed.');
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'cache_probe'];
            } else {
                $this->lineOk('cache: default store read/write OK');
                $lines[] = ['ok' => true, 'check' => 'cache_probe'];
            }
        } catch (Throwable $e) {
            $this->lineFail('Cache probe error: '.$e->getMessage());
            $ok = false;
            $lines[] = ['ok' => false, 'check' => 'cache_probe', 'detail' => $e->getMessage()];
        }

        $tenants = $tenantFilter !== null
            ? Tenant::query()->whereKey($tenantFilter)->cursor()
            : Tenant::query()->cursor();

        $tenantIds = [];
        foreach ($tenants as $tenant) {
            $tenantIds[] = $tenant->getKey();
        }

        if ($tenantIds === [] && $tenantFilter !== null) {
            $this->lineFail('Tenant not found for --tenant filter.');
            $ok = false;
            $lines[] = ['ok' => false, 'check' => 'tenant_filter'];
        }

        foreach ($tenantIds as $tid) {
            if ($this->roleHierarchyHasCycle($hierarchy, $tid)) {
                $this->lineFail("Role hierarchy cycle detected for tenant [{$tid}].");
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'hierarchy_cycle', 'tenant_id' => $tid];
            } else {
                $this->lineOk("hierarchy: no cycles (tenant {$tid})");
                $lines[] = ['ok' => true, 'check' => 'hierarchy_cycle', 'tenant_id' => $tid];
            }

            try {
                $scope = (string) $config->get('vaultrbac.freshness.scope', 'tenant');
                $v = $versions->getVersion($tid, $scope);
                $this->lineOk("permission cache version tenant={$tid} scope={$scope} => {$v}");
                $lines[] = ['ok' => true, 'check' => 'cache_version', 'tenant_id' => $tid, 'version' => $v];
            } catch (Throwable $e) {
                $this->lineFail("Permission cache version read failed: {$e->getMessage()}");
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'cache_version', 'tenant_id' => $tid];
            }
        }

        if ($tenantFilter === null && Schema::hasTable(VaultrbacTables::name('model_roles'))
            && Schema::hasTable(VaultrbacTables::name('roles'))) {
            $orphans = ModelRole::query()
                ->whereNotIn('role_id', Role::query()->select('id'))
                ->count();
            if ($orphans > 0) {
                $this->lineFail("Orphaned model_roles rows: {$orphans}.");
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'orphan_model_roles', 'count' => $orphans];
            } else {
                $this->lineOk('assignments: no orphaned model_roles');
                $lines[] = ['ok' => true, 'check' => 'orphan_model_roles'];
            }
        }

        if (Schema::hasTable(VaultrbacTables::name('encrypted_metadata'))) {
            try {
                $metaCount = EncryptedMetadata::query()->count();
                $this->lineOk("encrypted_metadata rows: {$metaCount}");
                $lines[] = ['ok' => true, 'check' => 'encrypted_metadata', 'count' => $metaCount];
            } catch (Throwable $e) {
                $this->lineFail('encrypted_metadata unreadable: '.$e->getMessage());
                $ok = false;
                $lines[] = ['ok' => false, 'check' => 'encrypted_metadata'];
            }
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode(['ok' => $ok, 'checks' => $lines], JSON_THROW_ON_ERROR));

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function lineOk(string $message): void
    {
        $this->line("<info>OK</info>  {$message}");
    }

    private function lineFail(string $message): void
    {
        $this->line("<error>FAIL</error> {$message}");
    }

    private function roleHierarchyHasCycle(HierarchyRepository $hierarchy, string|int $tenantId): bool
    {
        $edges = $hierarchy->roleHierarchyEdgesForTenant($tenantId);
        $adj = [];
        foreach ($edges as $edge) {
            $parent = (int) $edge->parent_role_id;
            $child = (int) $edge->child_role_id;
            $adj[$parent] ??= [];
            $adj[$parent][] = $child;
            $adj[$child] ??= [];
        }

        $visited = [];
        $stack = [];

        $visit = null;
        $visit = function (int $node) use (&$visit, &$visited, &$stack, $adj): bool {
            if (isset($stack[$node])) {
                return true;
            }
            if (isset($visited[$node])) {
                return false;
            }
            $visited[$node] = true;
            $stack[$node] = true;
            foreach ($adj[$node] ?? [] as $next) {
                if ($visit((int) $next)) {
                    return true;
                }
            }
            unset($stack[$node]);

            return false;
        };

        foreach (array_keys($adj) as $node) {
            if ($visit((int) $node)) {
                return true;
            }
        }

        return false;
    }
}
