import { test, expect } from '@playwright/test';
import { USERS, loginAs } from './helpers';

test.describe('Kepala Lab Golden Path E2E', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.kepalaLab);
    await expect(page).toHaveURL(/.*dashboard/);
  });

  test('kepala_lab sees lab-specific dashboard and PJ Lab menu items', async ({ page }) => {
    const sidebar = page.locator('aside');

    // Positive: accessible sections
    await expect(sidebar.getByRole('link', { name: /Dashboard/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Inventaris Lab/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Review Laporan/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Download Scanner/ })).toBeVisible();

    // Negative: global computer management and admin menus not visible
    await expect(sidebar.getByRole('link', { name: /Manajemen Akun/ })).not.toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Log Aktivitas/ })).not.toBeVisible();
  });

  test('kepala_lab can access lab inventory and review report views', async ({ page }) => {
    // Lab Inventory
    await page.goto('/lab/inventory');
    await expect(page).toHaveURL(/.*lab\/inventory/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Report reviews
    await page.goto('/lab/reports');
    await expect(page).toHaveURL(/.*lab\/reports/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Agent scanner download
    await page.goto('/agent/download');
    await expect(page).toHaveURL(/.*agent\/download/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');
  });

  test('kepala_lab is denied access to global admin management', async ({ page }) => {
    // Global computers
    const computersResponse = await page.goto('/computers');
    expect(computersResponse?.status()).toBe(403);

    // Global licenses
    const licensesResponse = await page.goto('/licenses');
    expect(licensesResponse?.status()).toBe(403);

    // Global softwares
    const softwareResponse = await page.goto('/softwares');
    expect(softwareResponse?.status()).toBe(403);

    // Accounts
    const accountsResponse = await page.goto('/accounts');
    expect(accountsResponse?.status()).toBe(403);
  });
});
