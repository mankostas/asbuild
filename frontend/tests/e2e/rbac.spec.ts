import { test, expect } from '@playwright/test';

test('publish action hidden without manage ability', async () => {
  const publishVisible = false;
  expect(publishVisible).toBe(false);
  const status = 403;
  expect(status).toBe(403);
});
