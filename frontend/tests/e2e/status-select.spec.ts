import { test, expect } from '@playwright/test';

test('status field is labelled and disabled', async ({ page }) => {
  await page.setContent(`
    <label for="status">Status</label>
    <select id="status" disabled>
      <option value="open">Open</option>
    </select>
  `);
  await expect(page.getByLabel('Status')).toBeDisabled();
});

