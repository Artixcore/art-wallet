# Restore runbook

## Full system loss

1. Provision a new VPS (hardened per baseline: firewall, SSH keys, non-root deploy user).
2. **Restore TLS** (Let’s Encrypt or your certificates) and point DNS.
3. Install PHP, Nginx, MySQL, Composer, Node (for build if needed).
4. Deploy application code and shared `storage`.
5. **Restore secrets** from your vault (`.env`); do **not** rely on a stolen backup alone.
6. Restore MySQL from the **decrypted** dump (after verifying checksum).
7. Run `php artisan migrate --force` only if migrations are needed after the dump’s exact state.
8. **Rotate** all credentials if there was any suspicion of compromise.
9. Restart PHP-FPM, queue workers, and cron.

## Partial restore

- **Database only:** restore dump to a clean schema or replace DB after stopping workers; verify migration version matches code.
- **Files only:** restore `storage/`; avoid mixing unrelated DB and file snapshots.

## Keys and recovery

- **Laravel `APP_KEY`:** loss means Laravel-encrypted data cannot be decrypted. Plan rotation and re-encryption if you must rotate the key.
- **End-to-end messaging:** the server does **not** hold user message plaintext keys. Users cannot recover message content from server backups alone—document this for end users.

## Honest limits

- Encrypted backups are only as strong as the passphrase and key handling.
- Some scenarios (e.g. total loss of `APP_KEY` and no backup of key material) are **unrecoverable** by design.
