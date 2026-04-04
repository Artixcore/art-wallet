# Backup runbook

## Principles

- Treat database dumps and `storage/` as **toxic assets**: they may contain sessions, tokens, and ciphertext. **Encrypt** any backup that leaves the server.
- **Never** store the backup decryption passphrase only on the same VPS. Use a password manager, offline key, or KMS-style secret store.

## MySQL

- Logical backup: `mysqldump` or equivalent. Run during low traffic; ensure consistency (single transaction where applicable for InnoDB).
- Restrict database user privileges for the app; backup user may use `SELECT` + lock privileges as required by your tool.

## Application files

- Include `storage/` (excluding `storage/logs` if you prefer log-only local retention) and **exclude** or **encrypt separately** `.env`—ideally reconstruct secrets from a vault rather than backing up plaintext `.env` offsite.

## Encryption

- Use **age** or **GPG** with a strong passphrase or key held **off** the server.
- Example pattern: dump to a temp file, encrypt to `backup-YYYY-MM-DD.sql.age`, delete temp file, upload encrypted artifact only.

## Rotation and retention

- Use grandfather / father / son or daily / weekly / monthly tiers.
- **Test restores** on a regular schedule; an untested backup is a guess.

## Verification script

- `scripts/ops/verify-backup-age.sh` checks that a backup artifact exists and is non-empty (optional gate after `age`/`gpg` encrypt step). Integrate into your backup cron after encryption and upload.

## Offsite

- Copy encrypted artifacts to another region or provider. Object storage with scoped access keys is acceptable; avoid public buckets.
