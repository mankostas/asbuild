import { test, expect } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('tenant deletion requires confirmation', async ({ page }) => {
  await page.goto('/users/tenants');
  await page.getByRole('button', { name: 'Delete' }).first().click();
  await page.getByRole('button', { name: 'Yes, delete' }).click();
});


// Additional documentation of CRUD flows
test.skip('super admin can create a tenant', async ({ page }) => {
  await page.goto('/users/tenants/create');
  await page.getByLabel('Name').fill('New Tenant');
  await page.getByLabel('Storage Quota (MB)').fill('100');
  await page.getByLabel('Phone').fill('123456');
  await page.getByLabel('Address').fill('Somewhere');
  await page.getByRole('button', { name: 'Save' }).click();
});

test.skip('super admin can edit a tenant', async ({ page }) => {
  await page.goto('/users/tenants/1/edit');
  await page.getByLabel('Name').fill('Updated Tenant');
  await page.getByRole('button', { name: 'Save' }).click();
});

test.skip('tenant create button hidden without tenants.create', async ({ page }) => {
  // Precondition: log in as a user missing the `tenants.create` ability.
  await page.goto('/users/tenants');
  await expect(page.getByRole('button', { name: 'Add Tenant' })).toBeHidden();
});

test.skip('tenant row actions respect granular abilities', async ({ page }) => {
  // Precondition: log in as a user that can view tenants but lacks update/delete.
  await page.goto('/users/tenants');
  const actionsToggle = page.locator('button[aria-haspopup="menu"]').first();
  await actionsToggle.click();
  await expect(page.getByRole('menuitem', { name: 'View' })).toBeVisible();
  await expect(page.getByRole('menuitem', { name: 'Edit' })).toHaveCount(0);
  await expect(page.getByRole('menuitem', { name: 'Delete' })).toHaveCount(0);
});

test.skip('impersonation is forbidden without tenants.manage', async ({ page }) => {
  // Precondition: log in as a user missing the `tenants.manage` ability.
  await page.goto('/users/tenants');
  const actionsToggle = page.locator('button[aria-haspopup="menu"]').first();
  await actionsToggle.click();
  await expect(page.getByRole('menuitem', { name: 'Impersonate' })).toHaveCount(0);

  // Even if the API is called manually, the backend should respond with 403.
  const response = await page.request.post('/api/tenants/1/impersonate');
  expect(response.status()).toBe(403);
});
