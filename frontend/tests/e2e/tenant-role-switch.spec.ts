import { test, expect } from '@playwright/test';

// Simulates the task-type builder flow when switching tenants.
test('full happy path for builder with tenant switching', async ({ page }) => {
  await page.setContent(`
    <select id="tenant">
      <option value="">Select tenant</option>
      <option value="a">Tenant A</option>
      <option value="b">Tenant B</option>
    </select>
    <div id="gating" role="status">Select tenant to configure permissions</div>
    <div id="roles" hidden></div>
    <div id="inspector" hidden>Inspector</div>
    <button id="save">Save</button>
    <div id="statuses"></div>
    <button id="add-status">Add status</button>
    <div id="transitions"></div>
    <button id="publish">Publish</button>
    <script>
      const tenants = { a: ['roleA1', 'roleA2'], b: ['roleB1'] };
      window.permissions = {};
      window.fieldRoles = { view: [], edit: [] };
      const statuses = [];
      const transitions = [];
      const tenantSelect = document.getElementById('tenant');
      tenantSelect.addEventListener('change', () => {
        const tenant = tenantSelect.value;
        const rolesDiv = document.getElementById('roles');
        rolesDiv.innerHTML = '';
        if (!tenant) {
          document.getElementById('gating').hidden = false;
          rolesDiv.hidden = true;
          document.getElementById('inspector').hidden = true;
          window.fieldRoles.view = [];
          window.fieldRoles.edit = [];
          return;
        }
        document.getElementById('gating').hidden = true;
        rolesDiv.hidden = false;
        document.getElementById('inspector').hidden = false;
        const available = tenants[tenant];
        window.fieldRoles.view = window.fieldRoles.view.filter((r) => available.includes(r));
        window.fieldRoles.edit = window.fieldRoles.edit.filter((r) => available.includes(r));
        available.forEach((r) => {
          const cb = document.createElement('input');
          cb.type = 'checkbox';
          cb.dataset.role = r;
          cb.addEventListener('change', () => {
            if (!window.permissions[tenant]) window.permissions[tenant] = {};
            window.permissions[tenant][r] = cb.checked;
            if (cb.checked) {
              window.fieldRoles.view.push(r);
            } else {
              window.fieldRoles.view = window.fieldRoles.view.filter((x) => x !== r);
            }
          });
          const label = document.createElement('label');
          label.appendChild(cb);
          label.appendChild(document.createTextNode(r));
          rolesDiv.appendChild(label);
        });
      });

      document.getElementById('save').addEventListener('click', () => {
        localStorage.setItem(
          'builder',
          JSON.stringify({ permissions: window.permissions, fieldRoles: window.fieldRoles, statuses, transitions })
        );
      });

      document.getElementById('add-status').addEventListener('click', () => {
        const name = 'Status' + (statuses.length + 1);
        statuses.push(name);
        const div = document.createElement('div');
        div.textContent = name;
        document.getElementById('statuses').appendChild(div);
        if (statuses.length > 1) {
          const from = statuses[statuses.length - 2];
          const to = name;
          transitions.push({ from, to });
          const tDiv = document.createElement('div');
          tDiv.textContent = from + '->' + to;
          document.getElementById('transitions').appendChild(tDiv);
        }
      });

      document.getElementById('publish').addEventListener('click', () => {
        localStorage.setItem('builderPublished', JSON.stringify({ statuses, transitions }));
      });

      window.addEventListener('load', () => {
        const saved = JSON.parse(localStorage.getItem('builderPublished') || 'null');
        if (saved) {
          saved.statuses.forEach((s) => {
            const div = document.createElement('div');
            div.textContent = s;
            document.getElementById('statuses').appendChild(div);
          });
          saved.transitions.forEach((tr) => {
            const tDiv = document.createElement('div');
            tDiv.textContent = tr.from + '->' + tr.to;
            document.getElementById('transitions').appendChild(tDiv);
          });
        }
      });
    </script>
  `);

  // gating until tenant picked
  await expect(page.getByRole('status')).toHaveText('Select tenant to configure permissions');
  await expect(page.locator('#roles')).toBeHidden();

  // pick tenant A
  await page.selectOption('#tenant', 'a');
  await expect(page.locator('#gating')).toBeHidden();
  await expect(page.locator('#roles label')).toHaveCount(2);
  await expect(page.locator('#inspector')).toBeVisible();

  // toggle permissions and save
  const roleA1 = page.locator('#roles input').first();
  await roleA1.check();
  await expect(roleA1).toBeChecked();
  await page.click('#save');

  // change tenant to B and ensure roles are sanitized
  await page.selectOption('#tenant', 'b');
  await expect(page.locator('#roles label')).toHaveCount(1);
  await expect(page.locator('#roles input')).not.toBeChecked();
  expect(await page.evaluate(() => window.fieldRoles.view.length)).toBe(0);

  // add statuses and transitions then publish
  await page.click('#add-status');
  await page.click('#add-status');
  await expect(page.locator('#statuses div')).toHaveCount(2);
  await expect(page.locator('#transitions div')).toHaveCount(1);
  await page.click('#publish');

  // reopen and verify persistence
  await page.reload();
  await expect(page.locator('#statuses div')).toHaveCount(2);
  await expect(page.locator('#transitions div')).toHaveCount(1);
});

