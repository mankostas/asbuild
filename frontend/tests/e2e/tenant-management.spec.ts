import { test, expect } from '@playwright/test';

const tenantManagementMarkup = `
  <div id="tenant-app">
    <label for="tenant-filter">Search tenants</label>
    <input id="tenant-filter" type="search" placeholder="Search by name" />
    <div role="group" aria-label="Tenant bulk actions">
      <button id="bulk-delete" type="button" disabled>Delete Selected</button>
      <button id="grant-delete" type="button">Grant delete ability</button>
      <button id="grant-manage" type="button">Grant manage ability</button>
    </div>
    <table aria-label="Tenants">
      <thead>
        <tr>
          <th scope="col">Select</th>
          <th scope="col">Name</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody id="tenant-rows"></tbody>
    </table>
    <p id="empty-state" role="status" hidden>No tenants found</p>
    <div id="toast" role="alert" hidden></div>
  </div>
  <script>
    (() => {
      const tenants = [
        { id: 1, name: 'Alpha Manufacturing' },
        { id: 2, name: 'Beta Logistics' },
        { id: 3, name: 'Gamma Research' }
      ];
      const abilities = new Set(['tenants.view']);
      const tbody = document.getElementById('tenant-rows');
      const toast = document.getElementById('toast');
      const emptyState = document.getElementById('empty-state');
      const bulkDelete = document.getElementById('bulk-delete');

      if (!tbody || !toast || !emptyState || !(bulkDelete instanceof HTMLButtonElement)) {
        return;
      }

      const selected = new Set();
      const rowMap = new Map();

      tenants.forEach((tenant) => {
        const row = document.createElement('tr');
        row.dataset.id = String(tenant.id);
        row.dataset.name = tenant.name.toLowerCase();

        const selectCell = document.createElement('td');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = String(tenant.id);
        checkbox.addEventListener('change', () => {
          if (checkbox.checked) {
            selected.add(tenant.id);
          } else {
            selected.delete(tenant.id);
          }
          updateBulkDeleteState();
        });
        selectCell.appendChild(checkbox);
        row.appendChild(selectCell);

        const nameCell = document.createElement('td');
        nameCell.textContent = tenant.name;
        row.appendChild(nameCell);

        const actionsCell = document.createElement('td');
        const impersonateButton = document.createElement('button');
        impersonateButton.type = 'button';
        impersonateButton.textContent = 'Impersonate';
        impersonateButton.setAttribute('data-ability', 'tenants.manage');
        actionsCell.appendChild(impersonateButton);

        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.textContent = 'Edit';
        editButton.setAttribute('data-ability', 'tenants.update');
        actionsCell.appendChild(editButton);

        row.appendChild(actionsCell);
        tbody.appendChild(row);
        rowMap.set(tenant.id, row);
      });

      function updateEmptyState() {
        const anyVisible = Array.from(rowMap.values()).some((row) => !row.hidden);
        emptyState.hidden = anyVisible;
      }

      function updateBulkDeleteState() {
        bulkDelete.disabled = selected.size === 0 || !abilities.has('tenants.delete');
      }

      function updateActionVisibility() {
        const actionElements = document.querySelectorAll('[data-ability]');
        actionElements.forEach((element) => {
          const ability = element.getAttribute('data-ability');
          if (!ability) {
            return;
          }
          const prefix = ability.split('.')[0] + '.manage';
          const allowed = abilities.has(ability) || abilities.has(prefix) || abilities.has('*');
          element.toggleAttribute('hidden', !allowed);
        });
      }

      const filterInput = document.getElementById('tenant-filter');
      if (filterInput instanceof HTMLInputElement) {
        filterInput.addEventListener('input', () => {
          const query = filterInput.value.toLowerCase().trim();
          rowMap.forEach((row) => {
            const matches = query === '' || row.dataset.name.indexOf(query) !== -1;
            row.hidden = !matches;
            if (!matches) {
              const input = row.querySelector('input[type="checkbox"]');
              if (input instanceof HTMLInputElement && input.checked) {
                input.checked = false;
                selected.delete(Number(input.value));
              }
            }
          });
          updateEmptyState();
          updateBulkDeleteState();
        });
      }

      bulkDelete.addEventListener('click', () => {
        if (bulkDelete.disabled) {
          return;
        }
        const removed = [];
        Array.from(selected).forEach((id) => {
          const row = rowMap.get(id);
          if (row) {
            row.remove();
            rowMap.delete(id);
            removed.push('Tenant #' + id);
          }
        });
        selected.clear();
        toast.hidden = removed.length === 0;
        toast.textContent = removed.length ? 'Deleted ' + removed.join(', ') : '';
        updateEmptyState();
        updateBulkDeleteState();
      });

      const grantDelete = document.getElementById('grant-delete');
      if (grantDelete instanceof HTMLButtonElement) {
        grantDelete.addEventListener('click', () => {
          abilities.add('tenants.delete');
          updateBulkDeleteState();
        });
      }

      const grantManage = document.getElementById('grant-manage');
      if (grantManage instanceof HTMLButtonElement) {
        grantManage.addEventListener('click', () => {
          abilities.add('tenants.manage');
          updateActionVisibility();
          updateBulkDeleteState();
        });
      }

      updateActionVisibility();
      updateEmptyState();
      updateBulkDeleteState();
    })();
  </script>
`;

test.describe('tenant management behaviours', () => {
  test.beforeEach(async ({ page }) => {
    await page.setContent(tenantManagementMarkup);
  });

  test('filters tenant list by search query and shows empty state when no matches', async ({ page }) => {
    const rows = page.locator('tbody tr');
    const visibleRows = page.locator('tbody tr:not([hidden])');
    await expect(rows).toHaveCount(3);

    await page.getByLabel('Search tenants').fill('Beta');
    await expect(visibleRows).toHaveCount(1);
    await expect(visibleRows.first()).toContainText('Beta Logistics');
    await expect(rows.filter({ hasText: 'Alpha Manufacturing' })).toBeHidden();

    await page.getByLabel('Search tenants').fill('Zeta');
    await expect(visibleRows).toHaveCount(0);
    await expect(page.locator('#empty-state')).toBeVisible();
  });

  test('enables bulk delete only with ability and removes selected tenants', async ({ page }) => {
    const bulkDelete = page.getByRole('button', { name: 'Delete Selected' });
    await expect(bulkDelete).toBeDisabled();

    await page.getByRole('button', { name: 'Grant delete ability' }).click();

    const firstRowCheckbox = page.locator('tbody tr').nth(0).locator('input[type="checkbox"]');
    const secondRowCheckbox = page.locator('tbody tr').nth(1).locator('input[type="checkbox"]');
    await firstRowCheckbox.check();
    await secondRowCheckbox.check();

    await expect(bulkDelete).toBeEnabled();
    await bulkDelete.click();

    await expect(page.locator('#toast')).toHaveText('Deleted Tenant #1, Tenant #2');
    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(bulkDelete).toBeDisabled();
  });

  test('hides gated actions until the manage ability is granted', async ({ page }) => {
    const firstRow = page.locator('tbody tr').first();
    const impersonateButton = firstRow.getByRole('button', { name: 'Impersonate' });
    const editButton = firstRow.getByRole('button', { name: 'Edit' });

    await expect(impersonateButton).toBeHidden();
    await expect(editButton).toBeHidden();

    await page.getByRole('button', { name: 'Grant manage ability' }).click();

    await expect(impersonateButton).toBeVisible();
    await expect(editButton).toBeVisible();
  });
});
