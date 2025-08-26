import { test, expect } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('reports range and export', async ({ page }) => {
  await page.goto('/reports/kpis');
  await page.getByRole('button', { name: 'Apply' }).click();
  await page.getByRole('button', { name: 'Export CSV' }).click();
});
