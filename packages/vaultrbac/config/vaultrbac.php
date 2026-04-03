<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Audit\NullAuditSink;
use Artwallet\VaultRbac\Cache\NullCacheInvalidator;
use Artwallet\VaultRbac\Context\DefaultAuthorizationContextFactory;
use Artwallet\VaultRbac\Hierarchy\NullRoleHierarchyProvider;
use Artwallet\VaultRbac\Resolvers\DenyAllPermissionResolver;
use Artwallet\VaultRbac\Security\NullSuperUserGuard;
use Artwallet\VaultRbac\Tenancy\NullTenantResolver;

return [

    /*
    |--------------------------------------------------------------------------
    | Database table names
    |--------------------------------------------------------------------------
    |
    | All package migrations and models use these names. Change them before
    | running migrations if you need to avoid collisions (defaults use vrb_).
    |
    */

    'tables' => [
        'tenants' => 'vrb_tenants',
        'teams' => 'vrb_teams',
        'permissions' => 'vrb_permissions',
        'roles' => 'vrb_roles',
        'permission_conditions' => 'vrb_permission_conditions',
        'approval_requests' => 'vrb_approval_requests',
        'role_permission' => 'vrb_role_permission',
        'model_roles' => 'vrb_model_roles',
        'model_permissions' => 'vrb_model_permissions',
        'role_hierarchy' => 'vrb_role_hierarchy',
        'permission_scopes' => 'vrb_permission_scopes',
        'permission_inheritance' => 'vrb_permission_inheritance',
        'encrypted_payloads' => 'vrb_encrypted_payloads',
        'super_user_actions' => 'vrb_super_user_actions',
        'cache_versions' => 'vrb_cache_versions',
        'audit_events' => 'vrb_audit_events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Load package migrations from vendor path
    |--------------------------------------------------------------------------
    |
    | Set to false if you published migrations to database/migrations and want
    | to run only the copied files (avoids running the same schema twice).
    |
    */

    'run_package_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | Concrete binding classes
    |--------------------------------------------------------------------------
    |
    | Swap these for your application implementations. Phase 1 ships secure
    | defaults: deny-all resolution, null tenant, no super-user bypass, no-op
    | audit and cache invalidation until the engine is wired (Phase 3+).
    |
    */

    'bindings' => [
        'tenant_resolver' => NullTenantResolver::class,
        'authorization_context_factory' => DefaultAuthorizationContextFactory::class,
        'permission_resolver' => DenyAllPermissionResolver::class,
        'role_hierarchy_provider' => NullRoleHierarchyProvider::class,
        'audit_sink' => NullAuditSink::class,
        'cache_invalidator' => NullCacheInvalidator::class,
        'super_user_guard' => NullSuperUserGuard::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Context defaults
    |--------------------------------------------------------------------------
    */

    'context' => [

        'session_attribute' => 'vaultrbac.session_id',

        'device_header' => 'X-Device-Id',

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache namespace (prefix for future cache keys)
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'store' => null,
        'prefix' => 'vaultrbac',
    ],

];
