import { test, expect } from '@playwright/test';

test('add button has accessible label', async ({ page }) => {
  await page.setContent(`<button aria-label="Προσθήκη" id="add-btn">Προσθήκη</button>`);
  const button = page.getByLabel('Προσθήκη');
  await expect(button).toBeVisible();
});
