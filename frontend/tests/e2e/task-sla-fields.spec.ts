import { test, expect } from '@playwright/test';

test('sla start and end fields are labelled and disabled', async ({ page }) => {
  await page.setContent(`
    <label for="sla_start">SLA Start</label>
    <input id="sla_start" disabled />
    <label for="sla_end">SLA End</label>
    <input id="sla_end" disabled />
  `);
  await expect(page.getByLabel('SLA Start')).toBeDisabled();
  await expect(page.getByLabel('SLA End')).toBeDisabled();
});
