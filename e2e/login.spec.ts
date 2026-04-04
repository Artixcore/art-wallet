import { test, expect } from '@playwright/test';

/**
 * Pilot E2E: login page renders (no AJAX envelope here—HTTP/HTML smoke).
 * Run: npm run test:e2e (requires APP_URL-compatible .env and migrations).
 */
test('login page shows email and password fields', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
});
