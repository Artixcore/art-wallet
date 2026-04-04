# Incident response runbook

## Assume breach when

- Unexpected admin sessions, spikes in failed SSH, outbound traffic to unknown hosts, ransomware-style disk encryption, or sudden `failed_jobs` growth with app errors.

## Immediate steps

1. **Contain:** isolate affected systems if possible; do not destroy disks before evidence.
2. **Preserve:** snapshot logs (copy off-box); note time and scope.
3. **Rotate:** session drivers, `APP_KEY` only with a documented migration plan, database passwords, API keys, RPC keys, and `OPS_MONITOR_TOKEN` if exposed.
4. **Review:** operator audit logs, `security_incidents` / security events if present, Nginx access logs.

## Communication

- Do not post secrets, stack traces with secrets, or `.env` contents in chat or tickets.

## Recovery

- Follow [restore.md](restore.md). Prefer restore from **known-good encrypted backup** over live DB if integrity is unknown.

## Post-incident

- Run `php artisan ops:validate` after changes.
- Re-run full restore drill on a staging host when feasible.
