import { test, expect } from '@playwright/test';

test('login page loads with form', async ({ page }) => {
    const response = await page.goto('/login');
    expect(response?.ok()).toBeTruthy();
    await expect(page.locator('#login')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
});
