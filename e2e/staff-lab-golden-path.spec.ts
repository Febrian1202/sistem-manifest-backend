import { test, expect } from '@playwright/test';
import { USERS, loginAs } from './helpers';

test.describe('Staff Lab Golden Path E2E', () => {
  test('staff_lab logs in and is redirected to lab inventory', async ({ page }) => {
    await loginAs(page, USERS.staffLab);
    await expect(page).toHaveURL(/.*lab\/inventory/);
  });

  test('staff_lab sees only staff-allowed sidebar items', async ({ page }) => {
    await loginAs(page, USERS.staffLab);
    await expect(page).toHaveURL(/.*lab\/inventory/);

    const sidebar = page.locator('aside');

    // Positive
    await expect(sidebar.getByRole('link', { name: /Komputer & Aset/ })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Download Scanner/ })).toBeVisible();

    // Negative: no admin or supervisory menus
    await expect(sidebar.getByRole('link', { name: /Review Laporan/ })).not.toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Manajemen Akun/ })).not.toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Katalog Software/ })).not.toBeVisible();
  });

  test('staff_lab is denied access to prohibited sections', async ({ page }) => {
    await loginAs(page, USERS.staffLab);

    const computersRes = await page.goto('/computers');
    expect(computersRes?.status()).toBe(403);

    const licensesRes = await page.goto('/licenses');
    expect(licensesRes?.status()).toBe(403);

    const softwaresRes = await page.goto('/softwares');
    expect(softwaresRes?.status()).toBe(403);

    const reportsRes = await page.goto('/reports');
    expect(reportsRes?.status()).toBe(403);

    const accountsRes = await page.goto('/accounts');
    expect(accountsRes?.status()).toBe(403);
  });
});
