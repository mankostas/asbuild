import { test, expect } from '@playwright/test';

test('file input is accessible', async ({ page }) => {
  await page.setContent('<input type="file" aria-label="photo" />');
  const input = page.getByLabel('photo');
  await expect(input).toBeVisible();
});
