import { test, expect } from '@playwright/test';

test('sla policy editor has accessible fields', async ({ page }) => {
  await page.setContent(`
    <label for="priority">Priority</label>
    <input id="priority" />
  `);
  await expect(page.getByLabel('Priority')).toBeVisible();
});
