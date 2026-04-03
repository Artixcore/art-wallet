# VaultRBAC schema DDL notes

Dialect targets: **MySQL 8.0+** and **PostgreSQL 15+** (SQLite used in automated tests).

## Team assignment uniqueness (`team_key`)

`team_id` is nullable for tenant-wide assignments. MySQL allows multiple `NULL` in a `UNIQUE` that includes `team_id`, so duplicate “global” rows were possible. The package adds `team_key = COALESCE(team_id, 0)` and a unique constraint on `(tenant_id, team_key, role_id, model_type, model_id)` (and the parallel tuple for direct permissions including `effect`). `team_key = 0` means global; non-zero matches a concrete `teams.id`.

## CHECK constraints (manual / optional)

Laravel’s schema builder does not emit portable named `CHECK` constraints across all drivers used in CI. For production on MySQL or PostgreSQL, consider adding:

- `role_hierarchy`: `child_role_id <> parent_role_id`
- `tenants`: `status` in `('active','suspended','deleted')`

## Audit and encrypted table names

Legacy installs may have `vrb_encrypted_payloads` and `vrb_audit_events`. Migration `2026_04_03_210017_*` renames them to `vrb_encrypted_metadata` and `vrb_audit_logs` when present. Config keys `encrypted_payloads` / `audit_events` remain as **aliases** to the new physical names.

## Partitioning and partial indexes

Large `audit_logs` tables: prefer time-based partitioning at the application/DBA layer; the package does not create partitions (engine-specific). Partial indexes (e.g. global permission catalog) are noted in the architecture plan; add via raw migrations if you standardize on PostgreSQL only.

## `permission_cache_versions` NULL semantics

`subject_type` defaults to `''` and `subject_id` defaults to `0` so a single `UNIQUE(tenant_id, scope, subject_type, subject_id)` works on MySQL without multiple-NULL unique quirks.
