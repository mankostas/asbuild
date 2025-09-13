import { test, expect } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('creates a role and assigns it to a user', async ({ page }) => {
  await page.goto('/roles');
  await page.getByRole('button', { name: 'Create' }).click();
  await page.getByPlaceholder('Role name').fill('Tester');
  await page.getByPlaceholder('Role description').fill('A test role');
  await page.getByRole('button', { name: 'Save' }).click();
  await page
    .getByRole('row', { name: /Tester/ })
    .getByRole('button', { name: 'Assign' })
    .click();
  await page.getByPlaceholder('Search user').fill('John');
  await page.getByRole('option', { name: /John Doe/ }).click();
  await page.getByRole('button', { name: 'Assign' }).click();
});

test.skip('shows user counts for each role', async ({ page }) => {
  await page.goto('/roles');
  await expect(page.getByRole('columnheader', { name: 'Users' })).toBeVisible();
  await expect(
    page.getByRole('row', { name: /ClientAdmin/ }).getByRole('cell', { name: /\d+/ })
  ).toBeVisible();
});

test.skip('shows ability summaries for each role', async ({ page }) => {
  await page.goto('/roles');
  await expect(page.getByRole('columnheader', { name: 'Abilities' })).toBeVisible();
  await expect(page.getByRole('row', { name: /ClientAdmin/ })).toContainText(/roles\.manage|1/);
});

test.skip('role form shows only allowed abilities', async ({ page }) => {
  await page.goto('/roles');
  await page.getByRole('button', { name: 'Create' }).click();
  await page.getByRole('combobox', { name: 'Abilities' }).click();
  await expect(page.getByRole('option', { name: 'types.manage' })).not.toBeVisible();
});

