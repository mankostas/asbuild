import { test, expect } from '@playwright/test';

// No running server in the environment; these tests document the expected
// authorization behaviour for tenant management under the Users menu.
test.skip('tenants actions are gated by abilities', async ({ page }) => {
  await page.goto('/users/tenants');
  await expect(page.getByRole('button', { name: 'Create' })).not.toBeVisible();
  await expect(page.getByRole('button', { name: 'Impersonate' })).not.toBeVisible();

  page.route('**/api/tenants', route =>
    route.fulfill({ status: 403, body: '{"message":"forbidden"}' })
  );
  page.route('**/api/tenants/1/impersonate', route =>
    route.fulfill({ status: 403, body: '{"message":"forbidden"}' })
  );

  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/tenants') && resp.status() === 403
    ),
    page.evaluate(() => fetch('/api/tenants', { method: 'POST' })),
  ]);
  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/tenants/1/impersonate') && resp.status() === 403
    ),
    page.evaluate(() => fetch('/api/tenants/1/impersonate', { method: 'POST' })),
  ]);
});

test.skip('tenants impersonation action succeeds', async ({ page }) => {
  await page.goto('/users/tenants');

  page.route('**/api/tenants/1/impersonate', route =>
    route.fulfill({ status: 200, body: '{"access_token":"t"}' })
  );
  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/tenants/1/impersonate') && resp.status() === 200
    ),
    page.getByRole('button', { name: 'Impersonate' }).first().click(),
  ]);
});
