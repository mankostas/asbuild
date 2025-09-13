import { test, expect } from '@playwright/test';

// Environment does not provide a running server; this test documents
// the expected redirect when navigating to a guarded route.
test.skip('redirects unauthorized routes to home', async ({ page }) => {
  await page.goto('/users/employees');
  await expect(page).toHaveURL('/');
});
