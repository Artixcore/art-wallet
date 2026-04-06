# VPS / bare-metal install (Composer + artgate)

`artixcore/artgate` is wired as a **path** dependency (`../artgate`). If you only deploy `art-wallet` under `/var/www/art-wallet`, Composer fails with:

`Source path "../artgate" is not found for package artixcore/artgate`

## Fix

1. Install **git** and **unzip** (Composer warns if `unzip` is missing):

```bash
sudo apt update && sudo apt install -y git unzip
```

2. Provide **artgate** next to the app (same parent directory):

**Option A — helper script (needs your real artgate Git URL):**

```bash
cd /var/www/art-wallet
export ARTGATE_GIT_URL="https://github.com/YOUR_ORG/artgate.git"
# optional: export ARTGATE_GIT_REF=main
composer run artgate:ensure
composer install
```

**Option B — one Composer alias:**

```bash
cd /var/www/art-wallet
export ARTGATE_GIT_URL="https://github.com/YOUR_ORG/artgate.git"
composer run install-with-artgate
```

**Option C — manual clone:**

```bash
sudo git clone https://github.com/YOUR_ORG/artgate.git /var/www/artgate
cd /var/www/art-wallet && composer install
```

The cloned repository **must** contain `composer.json` at its root (the public `github.com/artixcore/artgate` mirror does not ship that layout; use your private/source repo).

3. If Composer reports **lock file out of date**, run:

```bash
composer update --lock
```

or, after changing `composer.json`, `composer update` as appropriate.

## Layout

```text
/var/www/
  artgate/          ← artixcore/artgate (Composer path ../artgate)
  art-wallet/       ← this application (current directory for composer)
```

## Docker

See [digitalocean.md](digitalocean.md): the image clones artgate during build via `ARTGATE_GIT_URL`.
