# VaultRBAC (`artwallet/vaultrbac`)

Enterprise-oriented, tenant-aware RBAC for Laravel: direct and role-based permissions, hierarchy and wildcards, context-aware checks, temporary grants, approvals, audit logging, optional encrypted metadata, and versioned permission decision caching.

## Requirements

- PHP `^8.2`
- Laravel `illuminate/*` components `^11.0|^12.0|^13.0` (see [`composer.json`](composer.json))

## Installation

Require the package (path repository or VCS as you publish it):

```bash
composer require artwallet/vaultrbac
```

The service provider and `VaultRbac` facade alias are auto-discovered.

### Publish configuration

```bash
php artisan vendor:publish --tag=vaultrbac-config
```

Edit `config/vaultrbac.php`. All concrete implementations are listed under `bindings`; every entry must be a non-empty class name.

### Migrations

By default the package loads migrations from the vendor path (`run_package_migrations` => `true`). To copy migrations into your app (recommended before your first production deploy so you own the timeline):

```bash
php artisan vendor:publish --tag=vaultrbac-migrations
```

Then set `run_package_migrations` to `false` in config so the same schema is not applied twice.

Optional stubs:

```bash
php artisan vendor:publish --tag=vaultrbac-stubs
```

## Package layout (source tree)

The codebase uses these top-level namespaces under `src/`:

| Area | Purpose |
|------|---------|
| `Api/` | Public-facing query/DTO types (e.g. `AuthorizationQuery`, `Api\Dto\*`). Maps conceptually to a “DTO / public API” layer. |
| `Audit/` | Audit sink implementations. |
| `Cache/` | Cache invalidation helpers. |
| `Casts/` | Eloquent casts (e.g. encrypted JSON). |
| `Concerns/` | Reusable traits for app models (e.g. `AuthorizesWithVault`). |
| `Console/` | Artisan commands (`vaultrbac:*`, diagnostics). Alias for a canonical `Commands/` folder name. |
| `Context/` | `AuthorizationContext` and factory. |
| `Contracts/` | Interfaces for swapping implementations. |
| `Database/` | Table name helpers, blueprint macros. |
| `Enums/` | Status and domain enums. |
| `Events` / `Listeners` | Assignment lifecycle and audit recording. |
| `Exceptions/` | Package-specific exceptions. |
| `Facades/` | `VaultRbac` facade. |
| `Hierarchy/` | Role hierarchy provider. |
| `Http/` | Middleware and `IntegrationAuthorization`. |
| `Models/` | Eloquent models for RBAC tables. |
| `Repositories/` | Default Eloquent repository implementations. |
| `Resolvers/` | `DatabasePermissionResolver` and decorators (`Memoizing`, `VersionedCaching`, `Safe`). |
| `Security/` | `SuperUserGuard` implementations. |
| `Services/` | Assignment, temporary grants, approval workflow, cache admin. |
| `Support/` | Helpers (optional), request memo, small utilities. |
| `Tenancy/` | Tenant/team resolution and membership verification. |
| `Traits/` | Model traits. |

If you prefer a different physical folder naming convention (e.g. `DTOs/` instead of `Api/Dto/`, or `Commands/` instead of `Console/`), treat this table as the mapping; renaming is optional and would require updating namespaces and Composer autoload only.

## Security model (short)

- **Fail closed**: permission checks return denial on invalid input, missing tenant context when required, and internal errors when `integration.safe_resolver` is enabled (default).
- **Single decision path**: HTTP middleware, Gate abilities, Blade `@vaultcan`, and the `VaultRbac` facade should all delegate to `PermissionResolverInterface` (directly or via the facade), not reimplement matching rules.
- **Caching**: Decision caching is optional (`cache.decisions_enabled`). It is stamped with permission cache versions; misconfiguration of stores or TTL can affect availability, not permission broadening, when versions are enforced correctly.

See [`UPGRADE.md`](UPGRADE.md) for migration and operational notes.

## Integration quick reference

### Middleware (aliases)

Registered in [`VaultRbacServiceProvider`](src/VaultRbacServiceProvider.php), including:

- `vrb.permission`, `vrb.permission.any`, `vrb.permission.all`
- `vrb.role`, `vrb.role.any`
- `vrb.tenant.permission`, `vrb.context.permission`
- `vrb.approved.privilege`
- `vrb.tenant.resolve`, `vrb.permission.freshness`, `vrb.authorization.memo.flush`
- Legacy: `vault.permission`, `vault.role`, `vault.any-role`, `vault.tenant`, `vault.tenant.member`

### Gate

When `gate.enabled` is true, abilities default to `vaultrbac`, `vaultrbac.any`, `vaultrbac.all` (names configurable). Invalid ability arguments resolve to **deny**.

### Blade

When `blade.enabled` is true: `@vaultcan`, `@vaultrole`, and (if `blade.extended`) `@vaultcanany`, `@vaultcanall`, `@vaultroleany`, `@vaultcantenant`, `@vaultcanteam`. Errors inside directives resolve to **false** (fail closed for UI).

### Optional global helpers

Set `helpers.enabled` (and optionally `helpers.rbac_aliases_enabled`) in config, then require/autoload is handled by the provider.

## Factories (testing and seeding)

Model factories live under `database/factories/` with namespace `Artwallet\VaultRbac\Database\Factories`. Models that support `::factory()` declare `HasFactory` and `newFactory()`. Use them in tests or local seeders after migrations have run.

## Environment variables

Commonly used (all optional unless noted):

| Variable | Config area |
|----------|-------------|
| `VAULTRBAC_DEFAULT_TENANT_ID` | Default tenant when context has none |
| `VAULTRBAC_SAFE_RESOLVER` | Wrap resolver to deny on Throwable |
| `VAULTRBAC_GATE_*` | Gate ability names and toggles |
| `VAULTRBAC_BLADE_*` | Blade feature toggles |
| `VAULTRBAC_HELPERS_*` | Global helper toggles |
| `VAULTRBAC_CACHE_*` | Cache store, prefix, decision TTL, request memo |
| `VAULTRBAC_FRESHNESS_*` | Client permission version header middleware |
| `VAULTRBAC_AUDIT_*` | Audit logging and signing |
| `VAULTRBAC_ENCRYPT_*` | Metadata and approval payload encryption |

See `config/vaultrbac.php` for the full list and defaults.

## Artisan commands

Registered commands include (non-exhaustive): `vaultrbac:diagnose`, `vaultrbac:doctor`, `vaultrbac:sync-permissions`, `vaultrbac:cache-warm`, `vaultrbac:cache-flush`, `vaultrbac:audit-prune`, approval list/approve/reject, role/permission create/assign. Run `php artisan list vaultrbac` after install.

## Testing (package development)

```bash
cd packages/vaultrbac
composer install
composer test
```

Suites: `Unit`, `Integration`, `Feature`, `Security`, `Diagnostics`, `Concurrency`. Scripts: `composer test`, `composer test:security` (uses PHPUnit’s `security` group; classes carry `#[Group('security')]` where applicable), `composer test:diagnostics`, etc.

## License

MIT. See [`composer.json`](composer.json).
