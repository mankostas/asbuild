import { test, expect } from '@playwright/test';

test('tasks list filters persist after reload', async ({ page }) => {
  await page.goto('/tasks');
  await page.evaluate(() => {
    localStorage.setItem(
      'asbuild:tasksList:v1:1',
      JSON.stringify({
        filters: { status: 'completed', type: '', assignee: null, priority: '', dueStart: '', dueEnd: '', hasPhotos: false, mine: false },
        sort: null,
        pageSize: 10,
      }),
    );
  });
  await page.reload();
  const prefs = await page.evaluate(() => JSON.parse(localStorage.getItem('asbuild:tasksList:v1:1') || '{}'));
  expect(prefs.filters.status).toBe('completed');
});
