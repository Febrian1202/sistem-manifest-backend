import { test, expect } from '@playwright/test';
import { USERS, loginAs } from './helpers';

test.describe('Pimpinan Golden Path E2E', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.pimpinan);
    await expect(page).toHaveURL(/.*dashboard/);
  });

  test('pimpinan sees executive dashboard and read-only sidebar', async ({ page }) => {
    const sidebar = page.locator('aside');

    // Positive: accessible sections
    await expect(sidebar.getByRole('link', { name: /Dashboard/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Data Komputer/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Katalog Software/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Inventaris Lisensi/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Audit Kepatuhan/ })).toBeVisible();

    // Negative: admin management sections must not be visible
    await expect(sidebar.getByRole('link', { name: /Manajemen Akun/ })).not.toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Log Aktivitas/ })).not.toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Kirim ke PJ Lab/ })).not.toBeVisible();
  });

  test('pimpinan can navigate through inspection views without errors', async ({ page }) => {
    // Computers index
    await page.goto('/computers');
    await expect(page).toHaveURL(/.*computers/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Softwares index
    await page.goto('/softwares');
    await expect(page).toHaveURL(/.*softwares/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Licenses index
    await page.goto('/licenses');
    await expect(page).toHaveURL(/.*licenses/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Compliance index
    await page.goto('/compliance');
    await expect(page).toHaveURL(/.*compliance/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Reports hub
    await page.goto('/reports');
    await expect(page).toHaveURL(/.*reports/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Executive Report
    await page.goto('/reports/eksekutif');
    await expect(page).toHaveURL(/.*reports\/eksekutif/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');
  });

  test('pimpinan is denied access to admin-only pages', async ({ page }) => {
    // Accounts management
    const accountsResponse = await page.goto('/accounts');
    expect(accountsResponse?.status()).toBe(403);

    // Activity logs
    const logsResponse = await page.goto('/activity-logs');
    expect(logsResponse?.status()).toBe(403);

    // Agent download
    const agentResponse = await page.goto('/agent/download');
    expect(agentResponse?.status()).toBe(403);
  });
});
