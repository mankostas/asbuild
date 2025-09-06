import { test, expect } from '@playwright/test';

test('shows skeleton before rendering form', async ({ page }) => {
  await page.setContent(`
    <div id="skeleton">Loading...</div>
    <div id="form" style="display:none">Form Ready</div>
    <script>
      setTimeout(() => {
        document.getElementById('skeleton').remove();
        document.getElementById('form').style.display = 'block';
      }, 100);
    <\/script>
  `);
  await expect(page.locator('#skeleton')).toBeVisible();
  await page.waitForSelector('#form', { state: 'visible' });
  await expect(page.locator('#form')).toHaveText('Form Ready');
});
