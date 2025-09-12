import { test, expect } from '@playwright/test';

test('selecting a tenant loads statuses and leaves transitions empty', async ({ page }) => {
  await page.route('**/task-statuses', (route) => {
    const url = new URL(route.request().url());
    const tenantId = url.searchParams.get('tenant_id');
    const data = tenantId === '2'
      ? [{ slug: 'custom' }]
      : [{ slug: 'open' }, { slug: 'closed' }];
    route.fulfill({ json: { data } });
  });

  await page.setContent(`
    <select id="tenant">
      <option value="">Select tenant</option>
      <option value="1">Tenant 1</option>
      <option value="2">Tenant 2</option>
    </select>
    <ul id="statuses"></ul>
    <ul id="transitions"></ul>
    <script>
      document.getElementById('tenant').addEventListener('change', async (e) => {
        const id = e.target.value;
        const res = await fetch('/task-statuses?tenant_id=' + id);
        const json = await res.json();
        const statuses = (json.data || json).map((s) => s.slug);
        document.getElementById('statuses').innerHTML = statuses
          .map((s) => '<li>' + s + '</li>')
          .join('');
        document.getElementById('transitions').innerHTML = '';
      });
    </script>
  `);

  await page.selectOption('#tenant', '1');
  await expect(page.locator('#statuses li')).toHaveCount(2);
  await expect(page.locator('#transitions li')).toHaveCount(0);

  await page.selectOption('#tenant', '2');
  await expect(page.locator('#statuses li')).toHaveCount(1);
  await expect(page.locator('#transitions li')).toHaveCount(0);

  await page.evaluate(() => {
    const t = document.getElementById('transitions');
    t.innerHTML = '<li>open → closed</li>';
  });
  await expect(page.locator('#transitions li')).toHaveCount(1);
});

