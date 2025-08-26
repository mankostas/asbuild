import { test, expect } from '@playwright/test';

// No running server in the environment; these tests document the expected behaviour.
test.skip('shows 404 page for unknown routes', async ({ page }) => {
  await page.goto('/does-not-exist');
  await expect(page.getByText('Page not found')).toBeVisible();
});

test.skip('redirects unauthenticated users to login', async ({ page }) => {
  await page.goto('/appointments');
  await expect(page).toHaveURL(/auth\/login/);
});

