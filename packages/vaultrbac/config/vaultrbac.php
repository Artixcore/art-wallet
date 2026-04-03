<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Audit\DatabaseAuditSink;
use Artwallet\VaultRbac\Cache\NullCacheInvalidator;
use Artwallet\VaultRbac\Context\DefaultAuthorizationContextFactory;
use Artwallet\VaultRbac\Hierarchy\EloquentRoleHierarchyProvider;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Contracts\ApprovalRequestRepository;
use Artwallet\VaultRbac\Contracts\AuditLogRepository;
use Artwallet\VaultRbac\Contracts\EncryptedMetadataRepository;
use Artwallet\VaultRbac\Contracts\HierarchyRepository;
use Artwallet\VaultRbac\Contracts\PermissionAssignmentRepository;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionRepository;
use Artwallet\VaultRbac\Contracts\RoleAssignmentRepository;
use Artwallet\VaultRbac\Contracts\RoleRepository;
use Artwallet\VaultRbac\Contracts\TenantRepository;
use Artwallet\VaultRbac\Repositories\EloquentApprovalRequestRepository;
use Artwallet\VaultRbac\Repositories\EloquentAuditLogRepository;
use Artwallet\VaultRbac\Repositories\EloquentAuthorizationRepository;
use Artwallet\VaultRbac\Repositories\EloquentEncryptedMetadataRepository;
use Artwallet\VaultRbac\Repositories\EloquentHierarchyRepository;
use Artwallet\VaultRbac\Repositories\EloquentPermissionAssignmentRepository;
use Artwallet\VaultRbac\Repositories\EloquentPermissionCacheVersionRepository;
use Artwallet\VaultRbac\Repositories\EloquentPermissionRepository;
use Artwallet\VaultRbac\Repositories\EloquentRoleAssignmentRepository;
use Artwallet\VaultRbac\Repositories\EloquentRoleRepository;
use Artwallet\VaultRbac\Repositories\EloquentTenantRepository;
use Artwallet\VaultRbac\Resolvers\DatabasePermissionResolver;
use Artwallet\VaultRbac\Security\NullSuperUserGuard;
use Artwallet\VaultRbac\Services\ApprovalWorkflowService;
use Artwallet\VaultRbac\Services\AssignmentService;
use Artwallet\VaultRbac\Services\PermissionCacheAdminService;
use Artwallet\VaultRbac\Services\TemporaryGrantService;
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
        'tenant_roles' => 'vrb_tenant_roles',
        'tenant_permissions' => 'vrb_tenant_permissions',
        'temporary_permissions' => 'vrb_temporary_permissions',
        'role_expirations' => 'vrb_role_expirations',
        'permission_cache_versions' => 'vrb_permission_cache_versions',
        'encrypted_metadata' => 'vrb_encrypted_metadata',
        /*
         * Deprecated keys: same physical table as encrypted_metadata (upgrade path).
         */
        'encrypted_payloads' => 'vrb_encrypted_metadata',
        'super_user_actions' => 'vrb_super_user_actions',
        'cache_versions' => 'vrb_cache_versions',
        'audit_logs' => 'vrb_audit_logs',
        /*
         * Deprecated keys: same physical table as audit_logs (upgrade path).
         */
        'audit_events' => 'vrb_audit_logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Primary key strategy for RBAC tables (new publishes / stubs)
    |--------------------------------------------------------------------------
    |
    | bigint: auto-increment ids (default). uuid: char(36) primary keys — requires
    | publishing stub migrations and aligning morph ids; not applied to historical
    | package migrations automatically.
    |
    */

    'ids' => [
        'type' => env('VAULTRBAC_IDS_TYPE', 'bigint'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Load package migrations from vendor path
    |--------------------------------------------------------------------------
    |
    | Set to false if you published migrations to database/migrations and want
    | to run only the copied files (avoids running the same schema twice).
    |
    | Publish tags (console): vaultrbac-config, vaultrbac-migrations,
    | vaultrbac-stubs (includes migration.rbac.stub)
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
        'unauthenticated_status' => 401,
        'invalid_arguments_status' => 403,
        'integration_error_status' => 403,
        'treat_guest_as_unauthorized' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Framework integration (middleware, logging, request attributes)
    |--------------------------------------------------------------------------
    */

    'integration' => [
        'tenant_request_attribute' => 'vaultrbac.tenant_id',
        'safe_resolver' => env('VAULTRBAC_SAFE_RESOLVER', true),
        'defer_http_features_in_console' => false,
        'expose_integration_exceptions' => env('VAULTRBAC_EXPOSE_INTEGRATION_EXCEPTIONS', false),
        'log_channel' => env('VAULTRBAC_LOG_CHANNEL'),
        'log_level' => env('VAULTRBAC_LOG_LEVEL', 'error'),
        'verify_tenant_exists_on_resolve' => false,
        'verify_membership_after_tenant_resolve' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Gate (Phase 6)
    |--------------------------------------------------------------------------
    |
    | Registers Gate::define(ability) so policies and @can-style checks can
    | delegate to VaultRBAC: Gate::forUser($user)->allows($ability, [$name, $resource]).
    |
    */

    'gate' => [
        'enabled' => env('VAULTRBAC_GATE_ENABLED', true),
        'ability' => env('VAULTRBAC_GATE_ABILITY', 'vaultrbac'),
        'register_any_ability' => env('VAULTRBAC_GATE_REGISTER_ANY', true),
        'register_all_ability' => env('VAULTRBAC_GATE_REGISTER_ALL', true),
        'ability_any' => env('VAULTRBAC_GATE_ABILITY_ANY', 'vaultrbac.any'),
        'ability_all' => env('VAULTRBAC_GATE_ABILITY_ALL', 'vaultrbac.all'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blade conditionals (Phase 6)
    |--------------------------------------------------------------------------
    |
    | @vaultcan('permission.name', $optionalModel) and @vaultrole('role-name').
    |
    */

    'blade' => [
        'enabled' => env('VAULTRBAC_BLADE_ENABLED', true),
        'extended' => env('VAULTRBAC_BLADE_EXTENDED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global helpers (optional require in service provider)
    |--------------------------------------------------------------------------
    */

    'helpers' => [
        'enabled' => env('VAULTRBAC_HELPERS_ENABLED', false),
        'strict_context' => env('VAULTRBAC_HELPERS_STRICT_CONTEXT', false),
        'rbac_aliases_enabled' => env('VAULTRBAC_HELPERS_RBAC_ALIASES_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission cache admin (warm / flush)
    |--------------------------------------------------------------------------
    */

    'cache_admin' => [
        'warm_ttl_seconds' => (int) env('VAULTRBAC_CACHE_WARM_TTL', 3600),
        'assignment_subject_type' => env('VAULTRBAC_CACHE_ASSIGNMENT_SCOPE', 'assignment'),
        'throw_on_error' => env('VAULTRBAC_CACHE_ADMIN_THROW', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission cache version / client freshness (optional middleware)
    |--------------------------------------------------------------------------
    */

    'freshness' => [
        'enabled' => env('VAULTRBAC_FRESHNESS_ENABLED', false),
        'header' => env('VAULTRBAC_FRESHNESS_HEADER', 'X-VaultRbac-Permission-Version'),
        'scope' => env('VAULTRBAC_FRESHNESS_SCOPE', 'tenant'),
        'mismatch_status' => (int) env('VAULTRBAC_FRESHNESS_MISMATCH_STATUS', 403),
    ],

    /*
    |--------------------------------------------------------------------------
    | RequireApprovedPrivilege middleware defaults
    |--------------------------------------------------------------------------
    */

    'approval_middleware' => [
        'correlation_route_parameter' => 'correlation_id',
        'require_subject_is_authenticated_user' => true,
        'tenant_must_match_context' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync permissions from config (Phase 6)
    |--------------------------------------------------------------------------
    |
    | Consumed by `php artisan vaultrbac:sync-permissions --tenant=` or --global.
    | Each entry is a permission name string or an array with a `name` key.
    |
    */

    'sync' => [
        'permissions' => [],
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
        'permission_cache_admin' => PermissionCacheAdminService::class,
        'temporary_grant_service' => TemporaryGrantService::class,
        'approval_workflow' => ApprovalWorkflowService::class,
        'tenant_membership_verifier' => AssignmentBackedTenantMembershipVerifier::class,
        'role_repository' => EloquentRoleRepository::class,
        'permission_repository' => EloquentPermissionRepository::class,
        'tenant_repository' => EloquentTenantRepository::class,
        'approval_request_repository' => EloquentApprovalRequestRepository::class,
        'audit_log_repository' => EloquentAuditLogRepository::class,
        'encrypted_metadata_repository' => EloquentEncryptedMetadataRepository::class,
        'permission_cache_version_repository' => EloquentPermissionCacheVersionRepository::class,
        'role_assignment_repository' => EloquentRoleAssignmentRepository::class,
        'permission_assignment_repository' => EloquentPermissionAssignmentRepository::class,
        'hierarchy_repository' => EloquentHierarchyRepository::class,
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
