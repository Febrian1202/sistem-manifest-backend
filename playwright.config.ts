import { defineConfig, devices } from '@playwright/test';

const APP_URL = process.env.APP_URL || 'http://127.0.0.1:8001';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: APP_URL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: {
    command: 'php artisan serve --port=8001',
    url: APP_URL,
    reuseExistingServer: true,
    timeout: 120 * 1000,
  },
});
