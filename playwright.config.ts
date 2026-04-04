import { defineConfig, devices } from '@playwright/test';

/**
 * E2E pilot: run against `php artisan serve` (see webServer) or set PLAYWRIGHT_BASE_URL.
 */
export default defineConfig({
    testDir: './e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8123',
        trace: 'on-first-retry',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: process.env.PLAYWRIGHT_SKIP_WEBSERVER
        ? undefined
        : {
              command: 'php artisan migrate --force && php artisan serve --host=127.0.0.1 --port=8123',
              url: 'http://127.0.0.1:8123/login',
              reuseExistingServer: !process.env.CI,
              timeout: 120_000,
          },
});
