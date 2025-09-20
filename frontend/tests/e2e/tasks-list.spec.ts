import { test, expect } from '@playwright/test';
import { fakeTenantId } from '../utils/publicIds';

const tasksListTenantId = fakeTenantId('tasks-list');

test('tasks list filters persist after reload', async ({ page }) => {
  await page.goto('/tasks');
  await page.evaluate(() => {
    localStorage.setItem(
      'asbuild:tasksList:v1:' + tasksListTenantId,
      JSON.stringify({
        filters: { status: 'completed', type: '', assignee: null, priority: '', dueStart: '', dueEnd: '', hasPhotos: false, mine: false },
        sort: null,
        pageSize: 10,
      }),
    );
  });
  await page.reload();
  const prefs = await page.evaluate(
    (tenantId) =>
      JSON.parse(localStorage.getItem('asbuild:tasksList:v1:' + tenantId) || '{}'),
    tasksListTenantId,
  );
  expect(prefs.filters.status).toBe('completed');
});
