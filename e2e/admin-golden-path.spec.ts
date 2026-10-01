import { test, expect } from '@playwright/test';
import { USERS, loginAs } from './helpers';

test.describe('Admin Golden Path E2E', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.admin);
    await expect(page).toHaveURL(/.*dashboard/);
  });

  test('admin sees full dashboard and administrative navigation items', async ({ page }) => {
    // 1. Dashboard is visible
    await expect(page.locator('h1, h2, h3, header').first()).toBeVisible();

    // 2. Sidebar contains all Admin sections
    const sidebar = page.locator('aside');
    await expect(sidebar.getByRole('link', { name: /Dashboard/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Data Komputer/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Katalog Software/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Inventaris Lisensi/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Audit Kepatuhan/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Manajemen Akun/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Log Aktivitas/ })).toBeVisible();
  });

  test('admin can navigate through core operational views', async ({ page }) => {
    // Computers page
    await page.goto('/computers');
    await expect(page).toHaveURL(/.*computers/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Software catalog page
    await page.goto('/softwares');
    await expect(page).toHaveURL(/.*softwares/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Licenses page
    await page.goto('/licenses');
    await expect(page).toHaveURL(/.*licenses/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Compliance page
    await page.goto('/compliance');
    await expect(page).toHaveURL(/.*compliance/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Monitoring page
    await page.goto('/monitoring');
    await expect(page).toHaveURL(/.*monitoring/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Reports hub
    await page.goto('/reports');
    await expect(page).toHaveURL(/.*reports/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // User accounts management
    await page.goto('/accounts');
    await expect(page).toHaveURL(/.*accounts/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Activity logs
    await page.goto('/activity-logs');
    await expect(page).toHaveURL(/.*activity-logs/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    // Agent download
    await page.goto('/agent/download');
    await expect(page).toHaveURL(/.*agent\/download/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');
  });

  test('admin can view specific report preview pages', async ({ page }) => {
    await page.goto('/reports/eksekutif');
    await expect(page).toHaveURL(/.*reports\/eksekutif/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');

    await page.goto('/reports/kebutuhan-lisensi');
    await expect(page).toHaveURL(/.*reports\/kebutuhan-lisensi/);
    await expect(page.locator('body')).not.toContainText('403 Forbidden');
  });
});
