import { test } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('tenant deletion requires confirmation', async ({ page }) => {
  await page.goto('/tenants');
  await page.getByRole('button', { name: 'Delete' }).first().click();
  await page.getByRole('button', { name: 'Yes, delete' }).click();
});

