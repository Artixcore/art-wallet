# Permission cache version contract

## Tables

- **`vrb_cache_versions`**: string `cache_key` (primary) + monotonic `version` for ad hoc invalidation.
- **`vrb_permission_cache_versions`**: structured rows keyed by `(tenant_id, scope, subject_type, subject_id)` with `subject_type = ''` and `subject_id = 0` for tenant-wide bumps.

## Recommended `scope` values

| scope        | Meaning |
|-------------|---------|
| `subjects`  | Effective permissions for a subject (user/API client) changed. |
| `roles`     | Role catalog or role→permission graph affecting many subjects. |
| `permissions` | Global permission definitions / inheritance edges changed. |
| `wildcard`  | Wildcard parent permissions or implied edges changed. |

## When to bump `permission_cache_versions`

Bump the row for `(tenant_id, 'subjects', Subject::class, $subjectId)` when any of:

- `model_roles`, `model_permissions`, or `temporary_permissions` change for that subject in that tenant.
- `role_permission`, `role_hierarchy`, or `permission_inheritance` change and your cache layer materializes role expansion per subject without a separate graph version.

Bump `(tenant_id, 'roles', '', 0)` when tenant-level role catalog or `tenant_roles` toggles change which roles are valid.

Bump `(tenant_id, 'permissions', '', 0)` when `tenant_permissions` or global/tenant permission rows change definitions used in resolution.

## Cache key shape (application layer)

Example read-through key before loading a heavy set:

`vaultrbac:{tenant_id}:subject:{subject_type}:{subject_id}:eff:v{version}`

Where `version` is read from `permission_cache_versions` for that tuple, or `0` if missing (cold).

## Relation to `NullCacheInvalidator`

The default binding does not write these rows; production apps should swap in a `CacheInvalidator` that increments the appropriate `permission_cache_versions` row (or bumps `cache_versions` keys) inside assignment services or domain events.
