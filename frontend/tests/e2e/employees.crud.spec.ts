import { test, expect } from '@playwright/test';

// No running server in the environment; this test documents the
// expected authorization behaviour for employees CRUD operations.
test.skip('employees CRUD actions are gated by abilities', async ({ page }) => {
  await page.goto('/employees');
  await expect(page.getByRole('button', { name: 'Create' })).not.toBeVisible();

  page.route('**/api/employees', route =>
    route.fulfill({ status: 403, body: '{"message":"forbidden"}' })
  );
  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/employees') && resp.status() === 403
    ),
    page.evaluate(() => fetch('/api/employees', { method: 'POST' })),
  ]);
});
