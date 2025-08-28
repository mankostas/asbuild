import { test } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('creates a role and assigns it to a user', async ({ page }) => {
  await page.goto('/roles');
  await page.getByRole('button', { name: 'Create' }).click();
  await page.getByPlaceholder('Role name').fill('Tester');
  await page.getByRole('button', { name: 'Save' }).click();
  await page.getByRole('row', { name: /Tester/ }).getByRole('button', { name: 'Assign' }).click();
  await page.getByPlaceholder('Search user').fill('John');
  await page.getByRole('option', { name: /John Doe/ }).click();
  await page.getByRole('button', { name: 'Assign' }).click();
});

test.skip('role form shows only allowed abilities', async ({ page }) => {
  await page.goto('/roles');
  await page.getByRole('button', { name: 'Create' }).click();
  await page.getByRole('combobox', { name: 'Abilities' }).click();
  await expect(page.getByRole('option', { name: 'types.manage' })).not.toBeVisible();
});

