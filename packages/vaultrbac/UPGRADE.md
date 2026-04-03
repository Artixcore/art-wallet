# Upgrade guide (VaultRBAC)

## First-time install

1. Run `composer require artwallet/vaultrbac`.
2. Publish config: `php artisan vendor:publish --tag=vaultrbac-config`.
3. Either keep `run_package_migrations` `true` and run `php artisan migrate`, **or** publish migrations with `php artisan vendor:publish --tag=vaultrbac-migrations`, set `run_package_migrations` to `false`, then migrate once from your app’s `database/migrations` copy. Do not run both paths on the same database.
4. Implement or configure app-specific bindings as needed: `TenantResolver`, `TeamResolver`, `SuperUserGuard`, and any replacement repositories. Defaults use composite/null implementations suitable for tests; production apps should supply real resolvers.
5. Set `app.key` and, if you enable audit signing or encryption features, set `VAULTRBAC_AUDIT_SECRET` and Laravel encryption key appropriately.

## Configuration keys

- Table names live under `vaultrbac.tables`. Deprecated keys (`encrypted_payloads`, `audit_events`) map to the same physical tables as the canonical names; do not point them at different tables.
- `vaultrbac.bindings` must not contain empty strings; missing bindings throw `ConfigurationException` at container resolution time.

## Caching and permission versions

- If `cache.decisions_enabled` is `true`, ensure `vaultrbac.cache.store` points at a shared store in multi-server deployments.
- After bulk assignment or catalog changes, use `vaultrbac:cache-flush` or your app’s integration that bumps cache versions so clients do not rely on stale positive decisions.
- `freshness.enabled` middleware rejects requests when the client’s version header does not match the server’s scope version; plan rollouts so mobile/web clients refresh versions after permission changes.

## Safe resolver

- `integration.safe_resolver` (env `VAULTRBAC_SAFE_RESOLVER`) defaults to `true`. Disabling it surfaces resolver exceptions to callers; only do this in controlled debugging scenarios.

## Audit and encryption

- Turning on `audit.sign_rows` or metadata encryption after data exists may require backfills or migration steps specific to your deployment; test on a copy of production data first.
- `audit.enabled` `false` switches the audit sink to a no-op implementation.

## Publishing migrations after upgrades

When you upgrade the package and new migrations appear in `vendor/artwallet/vaultrbac/database/migrations`:

1. If you use published copies, diff new vendor migrations into your app’s migration directory with a new timestamp prefix, then run `migrate`.
2. If you load migrations from the vendor path, run `migrate` after deploy.

Always verify `run_package_migrations` matches your chosen strategy.

## Post-upgrade verification

- Run `php artisan vaultrbac:doctor` (or your CI equivalent) and the package test suite in a staging environment.
- Smoke-test Gate, one middleware alias, and one Blade `@vaultcan` path against a known user with known assignments.
