import { test, expect } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('shows abilities only after selecting a tenant', async ({ page }) => {
  await page.goto('/roles');
  await page.getByRole('link', { name: /Add Role|Create/ }).click();
  await expect(page.getByLabel('Abilities')).toHaveCount(0);
  await page.getByLabel('Tenant').click();
  await page.getByRole('option').first().click();
  await expect(page.getByLabel('Abilities')).toBeVisible();
});

test.skip('deselecting a feature hides its abilities immediately', async ({ page }) => {
  await page.goto('/roles');
  await page.getByRole('link', { name: /Add Role|Create/ }).click();
  await page.getByLabel('Tenant').click();
  await page.getByRole('option').first().click();
  await page.getByLabel('Abilities').click();
  await expect(page.getByRole('option', { name: 'tasks.view' })).toBeVisible();
  await page.goto('/users/tenants/1/edit');
  await page.getByLabel('Features').click();
  await page.getByRole('option', { name: /Tasks/ }).click();
  await page.goto('/roles');
  await page.getByRole('link', { name: /Add Role|Create/ }).click();
  await page.getByLabel('Tenant').click();
  await page.getByRole('option').first().click();
  await page.getByLabel('Abilities').click();
  await expect(page.getByRole('option', { name: 'tasks.view' })).toHaveCount(0);
});
