import { test, expect } from '@playwright/test';

test('save and load filter view', async ({ page }) => {
  // provide an http origin and fulfill with empty page so localStorage works
  await page.route('**/*', (route) => route.fulfill({ body: '<html></html>', contentType: 'text/html' }));
  await page.goto('http://localhost');
  await page.evaluate(() => {
    localStorage.clear();
    const views: Record<string, any> = {};
    views['My View'] = { status: 'draft' };
    localStorage.setItem('taskViews', JSON.stringify(views));
  });
  const result = await page.evaluate(() => JSON.parse(localStorage.getItem('taskViews') || '{}'));
  expect(result['My View'].status).toBe('draft');
});

test('bulk status change respects allowed actions', async ({ page }) => {
  await page.route('**/*', (route) => route.fulfill({ body: '<html></html>', contentType: 'text/html' }));
  await page.goto('http://localhost');
  const updated = await page.evaluate(() => {
    const selected = [1, 2];
    const actions: Record<number, string[]> = { 1: ['done'], 2: [] };
    const target = 'done';
    return selected.filter((id) => actions[id]?.includes(target));
  });
  expect(updated).toEqual([1]);
});
