# VPS / bare-metal install (Composer + artgate)

See also: [EC2 + Apache 2](ec2-apache.md) for AWS EC2, Apache `DocumentRoot` on `public/`, and TLS notes.

`artixcore/artgate` is wired as a **path** dependency at **`packages/artgate`** inside this repository. That path must exist (clone or copy) before Composer can install it.

If `packages/artgate` is missing, you may see:

`Source path "packages/artgate" is not found for package artixcore/artgate`

## Fix

1. Install **git** and **unzip** (Composer warns if `unzip` is missing):

```bash
sudo apt update && sudo apt install -y git unzip
```

2. From the app root (`/var/www/art-wallet`), provide **artgate** under `packages/artgate`.

**Recommended — plain `composer install`:** `composer.json` runs `scripts/ensure-artgate.sh` before install/update. Export your real artgate clone URL once per shell session:

```bash
cd /var/www/art-wallet
export ARTGATE_GIT_URL="https://github.com/YOUR_ORG/artgate.git"
# optional: export ARTGATE_GIT_REF=main
composer install
```

If `packages/artgate/composer.json` already exists (e.g. monorepo or prior clone), the script exits immediately. **`composer install --no-scripts` skips this hook** — clone artgate manually, run `composer run artgate:ensure`, or `git clone` into `packages/artgate` before install.

**Option B — explicit alias:**

```bash
cd /var/www/art-wallet
export ARTGATE_GIT_URL="https://github.com/YOUR_ORG/artgate.git"
composer run install-with-artgate
```

**Option C — manual clone:**

```bash
cd /var/www/art-wallet
mkdir -p packages
sudo git clone https://github.com/YOUR_ORG/artgate.git packages/artgate
composer install
```

The cloned repository **must** contain `composer.json` at its root (the public `github.com/artixcore/artgate` mirror does not ship that layout; use your private/source repo).

3. If Composer reports **lock file out of date**, run:

```bash
composer update --lock
```

or, after changing `composer.json`, `composer update` as appropriate.

## Layout

Only the app directory is required on the server; artgate lives **inside** it:

```text
/var/www/art-wallet/
  packages/
    artgate/        ← artixcore/artgate (Composer path packages/artgate)
  composer.json
  ...
```

## Docker

See [digitalocean.md](digitalocean.md): the image clones artgate during build via `ARTGATE_GIT_URL`.
