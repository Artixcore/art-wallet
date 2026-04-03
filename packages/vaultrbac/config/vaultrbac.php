<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Audit\DatabaseAuditSink;
use Artwallet\VaultRbac\Cache\NullCacheInvalidator;
use Artwallet\VaultRbac\Context\DefaultAuthorizationContextFactory;
use Artwallet\VaultRbac\Hierarchy\EloquentRoleHierarchyProvider;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Repositories\EloquentAuthorizationRepository;
use Artwallet\VaultRbac\Resolvers\DatabasePermissionResolver;
use Artwallet\VaultRbac\Security\NullSuperUserGuard;
use Artwallet\VaultRbac\Services\ApprovalWorkflowService;
use Artwallet\VaultRbac\Services\AssignmentService;
use Artwallet\VaultRbac\Tenancy\AssignmentBackedTenantMembershipVerifier;
use Artwallet\VaultRbac\Tenancy\CompositeTeamResolver;
use Artwallet\VaultRbac\Tenancy\CompositeTenantResolver;

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
    | Eloquent model classes
    |--------------------------------------------------------------------------
    */

    'models' => [
        'role' => Role::class,
        'permission' => Permission::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolution
    |--------------------------------------------------------------------------
    |
    | default_tenant_id: used when AuthorizationContext has no tenant (e.g.
    | single-tenant apps with NullTenantResolver). Leave null for strict mode.
    |
    */

    'default_tenant_id' => env('VAULTRBAC_DEFAULT_TENANT_ID'),

    'require_tenant_context' => true,

    'require_permission_definition' => true,

    'hierarchy' => [
        'max_expanded_nodes' => 256,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant / team context (Phase 4)
    |--------------------------------------------------------------------------
    |
    | Sources are evaluated in order; the first non-empty value wins.
    | Drivers: header, query, route, session, request_attribute, user_attribute.
    |
    | Example tenant header:
    | ['driver' => 'header', 'name' => 'X-Tenant-Id', 'cast' => 'int']
    |
    */

    'tenant' => [
        'sources' => [
            // ['driver' => 'header', 'name' => 'X-Tenant-Id', 'cast' => 'int'],
        ],
    ],

    'team' => [
        'sources' => [
            // ['driver' => 'header', 'name' => 'X-Team-Id', 'cast' => 'int'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP middleware status codes
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'missing_tenant_status' => 403,
        'unauthorized_status' => 403,
        'forbidden_tenant_status' => 403,
        'missing_permission_status' => 403,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit (Phase 5)
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'enabled' => env('VAULTRBAC_AUDIT_ENABLED', true),
        'genesis_prev_hash' => env('VAULTRBAC_AUDIT_GENESIS', 'genesis'),
        'register_listeners' => env('VAULTRBAC_AUDIT_LISTENERS', true),
        'secret' => env('VAULTRBAC_AUDIT_SECRET'),
        'sign_rows' => env('VAULTRBAC_AUDIT_SIGN_ROWS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption (Phase 5)
    |--------------------------------------------------------------------------
    */

    'encryption' => [
        'metadata' => [
            'enabled' => env('VAULTRBAC_ENCRYPT_METADATA', false),
        ],
        'approvals' => [
            'encrypt_payload' => env('VAULTRBAC_ENCRYPT_APPROVAL_PAYLOAD', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Concrete binding classes
    |--------------------------------------------------------------------------
    */

    'bindings' => [
        'tenant_resolver' => CompositeTenantResolver::class,
        'team_resolver' => CompositeTeamResolver::class,
        'authorization_context_factory' => DefaultAuthorizationContextFactory::class,
        'authorization_repository' => EloquentAuthorizationRepository::class,
        'permission_resolver' => DatabasePermissionResolver::class,
        'role_hierarchy_provider' => EloquentRoleHierarchyProvider::class,
        'audit_sink' => DatabaseAuditSink::class,
        'cache_invalidator' => NullCacheInvalidator::class,
        'super_user_guard' => NullSuperUserGuard::class,
        'assignment_service' => AssignmentService::class,
        'approval_workflow' => ApprovalWorkflowService::class,
        'tenant_membership_verifier' => AssignmentBackedTenantMembershipVerifier::class,
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
