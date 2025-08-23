import { test, expect } from '@playwright/test';
import path from 'path';

const filePath = path.join(__dirname, '..', 'index.html');

test('index page has title and app container', async ({ page }) => {
  await page.goto('file://' + filePath);
  await expect(page).toHaveTitle('Asbuild SPA');
  await expect(page.locator('#app')).toHaveCount(1);
});
