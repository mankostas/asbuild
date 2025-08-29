import { test, expect } from '@playwright/test';

// Environment does not provide a running server; this test documents
// the expected keyboard navigation behaviour.
test.skip('sidebar keyboard navigation', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Menu' }).click();
  await page.keyboard.press('Tab');
  await expect(page.getByRole('menuitem', { name: 'Tasks' })).toBeFocused();
  await page.keyboard.press('Tab');
  await expect(page.getByRole('menuitem', { name: 'Manuals' })).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('menuitem', { name: 'Tasks' })).not.toBeVisible();
});

test.skip('non-admin sidebar hides unauthorized items', async ({ page }) => {
  await page.goto('/');
  // In a real scenario, user would be authenticated with limited abilities.
  await expect(page.getByRole('link', { name: 'Task Types' })).not.toBeVisible();
});

