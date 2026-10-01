import { Page, expect } from '@playwright/test';

export const DEFAULT_PASSWORD = process.env.DEFAULT_USER_PASSWORD || 'KurangTahuBang123!';

export const USERS = {
  admin: {
    email: 'admin@usn.ac.id',
    password: DEFAULT_PASSWORD,
    name: 'Administrator',
  },
  pimpinan: {
    email: 'pimpinan@usn.ac.id',
    password: DEFAULT_PASSWORD,
    name: 'Dekan FTI',
  },
  kepalaLab: {
    email: 'kepalalab@usn.ac.id',
    password: DEFAULT_PASSWORD,
    name: 'Kepala Lab Komputer',
  },
  staffLab: {
    email: 'staff.lab@usn.ac.id',
    password: DEFAULT_PASSWORD,
    name: 'Operator Lab Komputer 1',
  },
};

export async function loginAs(page: Page, user: { email: string; password: string }) {
  await page.goto('/login');
  await expect(page.locator('input#email')).toBeVisible();

  await page.fill('input#email', user.email);
  await page.fill('input#password', user.password);

  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}
