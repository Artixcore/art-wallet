# Deploy runbook

## Layout

- Web root must be **only** `public/`. Document root in Nginx or Apache must not point at the repository root.
- Prefer release directories with a `current` symlink, e.g. `/var/www/artwallet/releases/<id>` and `/var/www/artwallet/current`, with **shared** `storage` and `.env` outside the release folder so deploys do not overwrite uploaded or cached files.

## Build steps (production)

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build` (or build assets in CI and ship artifacts)
3. `php artisan migrate --force` (after backup when schema changes)
4. `php artisan config:cache`, `route:cache`, `view:cache` when compatible with your routes and config
5. Restart PHP-FPM and queue workers after code change

## PHP-FPM

- Dedicated pool user; `display_errors=Off` in production.
- Enable OPcache; do not expose PHP version in HTTP headers.

## Queue workers

- `QUEUE_CONNECTION` is typically `database`. Run at least one worker via **systemd** or **Supervisor** with `Restart=always`.
- Failed jobs are probed by observability; alert on growth (see monitoring below).

## Scheduler

- Single cron entry, same user as the app:

  `* * * * * cd /path/to/current && php artisan schedule:run >> /dev/null 2>&1`

- The app schedules `RunObservabilityProbesJob` every two minutes so `system_health_checks` and queue signals stay fresh.

## Post-deploy validation

- `APP_ENV=production php artisan ops:validate` on the server (enforces `APP_DEBUG=false` and `APP_KEY` set).
- Optional: `APP_ENV=production php artisan ops:validate --permissions` warns if `storage/` or `bootstrap/cache` are not writable.
- In CI: `php artisan ops:validate --ci` or `bash scripts/ops/validate-guardrails.sh`.
- Hit `GET /up` for Laravel’s built-in health check.
- If `OPS_MONITOR_TOKEN` is set, verify the monitoring endpoint (below).

## Monitoring endpoint

- `OPS_MONITOR_TOKEN` (long random string) enables `GET /ops/monitor/health`.
- Send the token as `Authorization: Bearer <token>` or query `?token=<token>`.
- If the token is **unset**, the route returns **404** so the endpoint is not accidentally exposed without authentication.
- Response uses observability TTLs from `config/observability.php` (`OBSERVABILITY_TTL_*` env vars). HTTP **503** is returned when probes are **stale** (if `OPS_MONITOR_FAIL_ON_STALE` is true) or overall status is **critical**—configure your external monitor to alert on non-200.

## Secrets

- `.env` mode `600`, never committed. After deploy, confirm `APP_DEBUG=false` and `APP_ENV=production`.
