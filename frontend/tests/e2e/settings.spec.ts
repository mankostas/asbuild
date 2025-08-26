import { test } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('saves profile settings', async ({ page }) => {
  await page.goto('/settings/profile');
  await page.getByLabel('Name').fill('Updated Name');
  await page.getByRole('button', { name: 'Save Profile' }).click();
});

