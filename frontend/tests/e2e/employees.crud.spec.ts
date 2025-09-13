import { test, expect } from '@playwright/test';

// No running server in the environment; these tests document the expected
// authorization behaviour for employee management under the Users menu.
test.skip('employees actions are gated by abilities', async ({ page }) => {
  await page.goto('/users/employees');
  await expect(page.getByRole('button', { name: 'Create' })).not.toBeVisible();
  await expect(page.getByRole('button', { name: 'Impersonate' })).not.toBeVisible();
  await expect(page.getByRole('button', { name: 'Resend Invite' })).not.toBeVisible();

  page.route('**/api/employees', route =>
    route.fulfill({ status: 403, body: '{"message":"forbidden"}' })
  );
  page.route('**/api/employees/1/impersonate', route =>
    route.fulfill({ status: 403, body: '{"message":"forbidden"}' })
  );
  page.route('**/api/employees/1/resend-invite', route =>
    route.fulfill({ status: 403, body: '{"message":"forbidden"}' })
  );

  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/employees') && resp.status() === 403
    ),
    page.evaluate(() => fetch('/api/employees', { method: 'POST' })),
  ]);
  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/employees/1/impersonate') && resp.status() === 403
    ),
    page.evaluate(() => fetch('/api/employees/1/impersonate', { method: 'POST' })),
  ]);
  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/employees/1/resend-invite') && resp.status() === 403
    ),
    page.evaluate(() => fetch('/api/employees/1/resend-invite', { method: 'POST' })),
  ]);
});

test.skip('employees impersonation and invite-resend actions succeed', async ({ page }) => {
  await page.goto('/users/employees');

  page.route('**/api/employees/1/impersonate', route =>
    route.fulfill({ status: 200, body: '{"access_token":"a","refresh_token":"b"}' })
  );
  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/employees/1/impersonate') && resp.status() === 200
    ),
    page.getByRole('button', { name: 'Impersonate' }).first().click(),
  ]);

  page.route('**/api/employees/1/resend-invite', route =>
    route.fulfill({ status: 200, body: '{"status":"ok"}' })
  );
  await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/api/employees/1/resend-invite') && resp.status() === 200
    ),
    page.getByRole('button', { name: 'Resend Invite' }).first().click(),
  ]);
});
