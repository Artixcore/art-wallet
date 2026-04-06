# Deploy ArtWallet on DigitalOcean App Platform

This app ships a multi-stage [Dockerfile](../../Dockerfile), [.dockerignore](../../.dockerignore), and an App Platform template [.do/app.yaml](../../.do/app.yaml).

## artixcore / artgate dependency

Composer expects `artixcore/artgate` at **`../artgate`** relative to the app root. Inside the Docker image that path is **`/var/www/artgate`**.

**Option A — build-time Git clone (default in Dockerfile)**  

Set a **build-time** secret `ARTGATE_GIT_URL` to an HTTPS Git URL (and optionally `ARTGATE_GIT_REF` for branch/tag). The Dockerfile clones it to `/var/www/artgate` before `composer install`.

- Private repo: use a read-only token in the URL or follow DigitalOcean’s docs for build-time access to private Git dependencies.

**Option B — monorepo context**  

If both `art-wallet` and `artgate` live in one checkout, build from the parent directory with a custom Dockerfile that copies both trees into `/var/www/html` and `/var/www/artgate`, or adjust `COPY` paths to match your layout.

**Option C — Composer VCS**  

Point `artixcore/artgate` at a Git or Packagist source and remove the `path` repository for production (local dev can keep `path` via a separate `composer.local.json` workflow if needed).

## One-time setup checklist

1. **GitHub (or GitLab)**: push this repository; replace `PLACEHOLDER_ORG/art-wallet` in `.do/app.yaml` (three places) or connect the repo in the App Platform UI.
2. **Managed MySQL**: the template adds a `db` cluster; Laravel receives `DB_*` via `${db.*}` references.
3. **Build secret `ARTGATE_GIT_URL`**: set for **web**, **queue**, and **migrate** components (each build may require it unless you deduplicate images in DO).
4. **Runtime secret `APP_KEY`**: generate locally with `php artisan key:generate --show` and add the same value as an **encrypted** variable on **web**, **queue**, and the **migrate** PRE_DEPLOY job. Laravel will refuse to boot without it (`config:cache`, `migrate`, queues).
5. **`APP_URL`**: set to your live HTTPS URL (e.g. `https://your-app.ondigitalocean.app`).
6. **Trust / Sanctum**: if you use custom domains, set `SANCTUM_STATEFUL_DOMAINS` and session cookie domain as needed.

## Runtime defaults in the template

- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` — ensure migrations have run (PRE_DEPLOY job) so `sessions` / `cache` tables exist, or switch to Redis and add a DO Redis database plus `REDIS_*` env vars.
- Logs: `LOG_CHANNEL=stderr` for container logs.
- `ARTGATE_RUN_PACKAGE_MIGRATIONS=true` matches local `.env.example`; adjust if you publish package migrations into `database/migrations` instead.

## Commands reference

| Component | Command |
|-----------|---------|
| Web | `/usr/local/bin/entrypoint-web.sh` → `php artisan serve` on `$PORT` (8080) |
| Worker | `/usr/local/bin/entrypoint-worker.sh` → `php artisan queue:work` |
| PRE_DEPLOY | `php artisan migrate --force --no-interaction` |

## Local Docker smoke test

```bash
docker build \
  --build-arg ARTGATE_GIT_URL=https://github.com/your-org/artgate.git \
  --build-arg ARTGATE_GIT_REF=main \
  -t art-wallet:local .

docker run --rm -p 8080:8080 \
  -e APP_KEY=base64:YOUR_GENERATED_KEY \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=artwallet \
  -e DB_USERNAME=artwallet \
  -e DB_PASSWORD=secret \
  art-wallet:local
```

Adjust DB host for your environment.

## Production hardening (later)

- Replace `php artisan serve` with **nginx + php-fpm** or **FrankenPHP** in the same image.
- Add **Redis** for cache/queue/session under load.
- Pin image digests and enable **non-root** user tweaks if your orchestrator allows.
- Review CSP / fonts (see application `SecurityHeaders` middleware) for your asset CDN choices.

## doctl

```bash
doctl apps create --spec .do/app.yaml
```

Validate and edit placeholders before applying.
