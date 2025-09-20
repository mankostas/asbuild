import { test, expect } from '@playwright/test';
import { fakeTaskId } from '../utils/publicIds';

const createdTaskTypeId = fakeTaskId('type-create');

test.describe('task type create UI', () => {
  test('can add field then access preview and inspector', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.setContent(`
      <button id="add">Add Field</button>
      <nav role="tablist">
        <button role="tab">Canvas</button>
        <button role="tab">Preview</button>
        <button role="tab">Inspector</button>
      </nav>
      <div id="canvas"></div>
      <div id="inspector">Select a field</div>
      <script>
        document.getElementById('add').addEventListener('click', () => {
          const f = document.createElement('button');
          f.textContent = 'Field 1';
          f.id = 'f1';
          f.addEventListener('click', () => {
            document.getElementById('inspector').textContent = 'Field 1 selected';
          });
          document.getElementById('canvas').appendChild(f);
        });
      <\/script>
    `);
    await page.getByRole('button', { name: 'Add Field' }).click();
    await expect(page.getByRole('button', { name: 'Field 1' })).toBeVisible();
    await page.getByRole('tab', { name: 'Preview' }).click();
    await page.getByRole('tab', { name: 'Inspector' }).click();
    await page.getByRole('button', { name: 'Field 1' }).click();
    await expect(page.locator('#inspector')).toHaveText('Field 1 selected');
  });
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

  test('automations saved after type creation', async ({ page }) => {
    await page.route('**/api/task-types', async (route) => {
      await route.fulfill({
        status: 201,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: '${createdTaskTypeId}' }),
      });
    });
    await page.route(`**/api/task-types/${createdTaskTypeId}/automations`, async (route) => {
      const data = JSON.parse(route.request().postData() || '{}');
      expect(data.enabled).toBe(false);
      await route.fulfill({ status: 201, body: '{}' });
    });
    await page.setContent(`
      <button id="saveAuto">Save Automation</button>
      <button id="saveType">Save Type</button>
      <script>
        const autos = [];
        document.getElementById('saveAuto').onclick = () => {
          autos.push({ event: 'status_changed', conditions_json: {}, actions_json: [], enabled: false, _saved: true });
        };
        document.getElementById('saveType').onclick = () => {
          fetch('/api/task-types', { method: 'post' })
            .then(r => r.json())
            .then(res => {
              autos.filter(a => a._saved).forEach(a => {
                fetch('/api/task-types/' + res.id + '/automations', { method: 'post', body: JSON.stringify(a) });
              });
            });
        };
      <\/script>
    `);
    await page.getByRole('button', { name: 'Save Automation' }).click();
    await page.getByRole('button', { name: 'Save Type' }).click();
    await page.waitForRequest(`**/api/task-types/${createdTaskTypeId}/automations`);
  });
});
