# ArtWallet Playwright E2E (pilot)

## Prerequisites

- Copy `.env` with `APP_KEY` set and a working `DB_*` (SQLite file or MySQL).
- Run migrations: `php artisan migrate`.

## Run locally

```bash
npm ci
npx playwright install chromium
npm run test:e2e
```

By default `playwright.config.ts` starts `php artisan serve` on port **8123**. To use an existing server:

```bash
set PLAYWRIGHT_SKIP_WEBSERVER=1
set PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000
npm run test:e2e
```

## CSRF note

Laravel **disables CSRF verification during PHPUnit** (`PreventRequestForgery::runningUnitTests`). Browser-based E2E against `php artisan serve` uses a normal runtime, so CSRF is enforced for state-changing requests. Add future specs that perform authenticated POSTs without tokens to assert **419** responses.
