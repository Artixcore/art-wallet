# ArtWallet operations

Versioned runbooks for self-hosted production deployments. These align with the production security blueprint: treat the VPS as part of the threat surface, not just hosting.

## Runbooks

- [Deploy](deploy.md) — release layout, PHP-FPM, queue workers, scheduler, caching.
- [Backup](backup.md) — encrypted MySQL and file backups, rotation, offsite storage.
- [Restore](restore.md) — full and partial recovery, keys, and what cannot be recovered by design.
- [Incident response](incident.md) — containment, evidence, rotation, communication.

## External monitoring

When `OPS_MONITOR_TOKEN` is set in `.env`, uptime and monitoring tools can call `GET /ops/monitor/health` with the token (see [deploy.md](deploy.md#monitoring-endpoint)). The JSON reflects the same observability TTLs and queue failed-job signals as the operator dashboard, without session authentication.

## Stack reference

- Laravel 13, PHP 8.3+, MySQL in production, queue default `database`, scheduler runs observability probes every two minutes (`routes/console.php`).
