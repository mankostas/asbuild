import { test } from '@playwright/test';

// No running server in the environment; this test documents the expected flow.
test.skip('logs in with valid credentials', async ({ page }) => {
  await page.goto('/auth/login');
  await page.getByPlaceholder('Type your email').fill('user@example.com');
  await page
    .getByPlaceholder('8+ characters, 1 capitat letter')
    .fill('Password1');
  await page.getByRole('button', { name: 'Sign in' }).click();
});

