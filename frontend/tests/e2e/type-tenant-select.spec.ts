import { test, expect } from '@playwright/test';

// Simulates tenant selection with async search and global option.
test('builder tenant select with search and global option', async ({ page }) => {
  await page.setContent(`
    <input id="tenant" list="tenant-options" />
    <datalist id="tenant-options">
      <option value="Global" data-id=""></option>
    </datalist>
    <div id="gating" role="status">Select tenant to configure permissions</div>
    <div id="roles" hidden></div>
    <script>
      const tenants = { 'Tenant A': ['roleA1', 'roleA2'], 'Tenant B': ['roleB1'] };
      const allNames = Object.keys(tenants);
      const input = document.getElementById('tenant');
      const list = document.getElementById('tenant-options');
      input.addEventListener('input', () => {
        const q = input.value.toLowerCase();
        if (q.length >= 3) {
          list.innerHTML = '<option value="Global" data-id=""></option>' +
            allNames
              .filter((n) => n.toLowerCase().includes(q))
              .map((n) => '<option value="' + n + '" data-id="' + n + '"></option>')
              .join('');
        }
      });
      input.addEventListener('change', () => {
        const opt = Array.from(list.options).find((o) => o.value === input.value);
        const gating = document.getElementById('gating');
        const rolesDiv = document.getElementById('roles');
        if (!opt || !opt.dataset.id) {
          gating.hidden = false;
          rolesDiv.hidden = true;
          rolesDiv.innerHTML = '';
          return;
        }
        gating.hidden = true;
        rolesDiv.hidden = false;
        rolesDiv.innerHTML = tenants[opt.dataset.id]
          .map((r) => '<label><input type="checkbox">' + r + '</label>')
          .join('');
      });
    </script>
  `);

  // gating until tenant picked
  await expect(page.getByRole('status')).toHaveText('Select tenant to configure permissions');
  await expect(page.locator('#roles')).toBeHidden();

  // search and pick tenant A
  await page.fill('#tenant', 'Tenant A');
  await page.keyboard.press('Enter');
  await expect(page.locator('#gating')).toBeHidden();
  await expect(page.locator('#roles label')).toHaveCount(2);

  // switch to tenant B
  await page.fill('#tenant', 'Tenant B');
  await page.keyboard.press('Enter');
  await expect(page.locator('#roles label')).toHaveCount(1);

  // switch to Global
  await page.fill('#tenant', 'Global');
  await page.keyboard.press('Enter');
  await expect(page.locator('#gating')).toBeVisible();
  await expect(page.locator('#roles')).toBeHidden();
});
