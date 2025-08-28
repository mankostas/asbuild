import { test } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('creates a team and adds members', async ({ page }) => {
  await page.goto('/teams');
  await page.getByRole('button', { name: 'Create' }).click();
  await page.getByPlaceholder('Team name').fill('Alpha');
  await page.getByRole('button', { name: 'Save' }).click();
  await page.getByRole('row', { name: /Alpha/ }).getByRole('button', { name: 'Edit' }).click();
  await page.getByPlaceholder('Add members').fill('John');
  await page.getByRole('option', { name: /John Doe/ }).click();
  await page.getByRole('button', { name: 'Save' }).click();
});
