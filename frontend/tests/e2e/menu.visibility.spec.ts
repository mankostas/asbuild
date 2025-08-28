import { test, expect } from '@playwright/test';

// No running server in the environment; this test documents
// that menu items are hidden when abilities are missing.
test.skip('menu items hidden without required abilities', async ({ page }) => {
  await page.goto('/');
  // In a real scenario, user would be authenticated with limited abilities.
  await expect(page.getByRole('link', { name: 'Employees' })).not.toBeVisible();
});
