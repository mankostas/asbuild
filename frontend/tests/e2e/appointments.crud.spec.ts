import { test } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('appointments CRUD happy path', async ({ page }) => {
  await page.goto('/appointments');
  await page.getByRole('button', { name: 'New Appointment' }).click();
  await page.getByPlaceholder('Title').fill('My appointment');
  await page.getByRole('button', { name: 'Save' }).click();
  await page.getByText('My appointment').click();
  await page.getByRole('button', { name: 'Edit' }).click();
  await page.getByRole('button', { name: 'Delete' }).click();
});
