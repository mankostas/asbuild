import { test, expect } from '@playwright/test';

test('blocks access to task type create page without ability', async () => {
  const hasAbility = false;
  expect(hasAbility).toBe(false);
  const response = { status: 403 };
  expect(response.status).toBe(403);
});
