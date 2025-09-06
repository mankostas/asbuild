import { test, expect } from '@playwright/test';

test('sla start field is labelled and disabled', async ({ page }) => {
  await page.setContent(`
    <label for="sla_start">SLA Start</label>
    <input id="sla_start" disabled />
  `);
  await expect(page.getByLabel('SLA Start')).toBeDisabled();
});
