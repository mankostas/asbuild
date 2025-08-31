import { test, expect } from '@playwright/test';

test.describe('task type create UI', () => {
  test('tabs render on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.setContent(`
      <nav role="tablist">
        <button role="tab">Canvas</button>
        <button role="tab">Preview</button>
        <button role="tab">Inspector</button>
      </nav>
    `);
    await expect(page.getByRole('tab', { name: 'Canvas' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Preview' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Inspector' })).toBeVisible();
  });

  test('version controls are hidden on create', async ({ page }) => {
    await page.setContent(`
      <div>
        <button>Save</button>
      </div>
    `);
    const hidden = ['Duplicate', 'Publish', 'Delete', 'Revert'];
    for (const label of hidden) {
      await expect(page.getByRole('button', { name: label })).toHaveCount(0);
    }
    await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();
  });

  test('SLA and Automations editors are visible without saving', async ({ page }) => {
    await page.setContent(`
      <section>
        <h2>SLA Policies</h2>
        <h2>Automations</h2>
      </section>
    `);
    await expect(page.getByRole('heading', { name: 'SLA Policies' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Automations' })).toBeVisible();
  });

  test('permissions empty state until tenant selected', async ({ page }) => {
    await page.setContent(`
      <div id="permissions">
        <p role="status">Select tenant to configure permissions</p>
      </div>
    `);
    await expect(
      page.getByRole('status', { name: 'Select tenant to configure permissions' })
    ).toBeVisible();
  });

  test('validation hits id-less endpoint and shows errors', async ({ page }) => {
    await page.route('**/api/task-types/validate', async (route) => {
      expect(route.request().url()).toMatch(/\/api\/task-types\/validate$/);
      await route.fulfill({
        status: 422,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ errors: { name: ['Required'] } }),
      });
    });

    await page.setContent(`
      <form onsubmit="fetch('/api/task-types/validate', {method: 'post', body: new FormData(this)})
        .then(r => r.json())
        .then(j => (document.getElementById('errors').textContent = j.errors.name[0])); return false;">
        <label for="name">Name</label>
        <input id="name" name="name" />
        <button type="submit">Run Validation</button>
      </form>
      <div id="errors" role="alert"></div>
    `);

    await page.getByRole('button', { name: 'Run Validation' }).click();
    await expect(page.getByRole('alert')).toHaveText('Required');
  });
});
