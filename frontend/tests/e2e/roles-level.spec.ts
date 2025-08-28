import { test, expect } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('shows custom level in roles list after form submit', async ({ page }) => {
  await page.goto('/roles');
  await page.getByRole('link', { name: /Add Role|Create/ }).click();
  await page.getByLabel('Name').fill('Level Role');
  await page.getByLabel('Slug').fill('level-role');
  await page.getByLabel('Level').fill('3');
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(
    page.getByRole('row', { name: /Level Role/ }).getByText('3')
  ).toBeVisible();
});
