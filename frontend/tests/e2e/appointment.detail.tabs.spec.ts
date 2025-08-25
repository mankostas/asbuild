import { test } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('tab switching and unsaved changes guard', async ({ page }) => {
  await page.goto('/appointments/1');
  await page.getByRole('tab', { name: 'Photos' }).click();
  await page.getByRole('tab', { name: 'Comments' }).click();
  await page.getByRole('textbox').first().fill('some value');
  await page.getByRole('link', { name: 'Appointments' }).click();
  await page.getByRole('button', { name: 'Leave' }).click();
});
