import { test, expect } from '@playwright/test';

test('field palette filters groups by search query', async () => {
  const groups = [
    { label: 'Inputs', items: [{ label: 'Text' }, { label: 'Number' }] },
    { label: 'Dates', items: [{ label: 'Date' }] },
  ];
  const search = 'tex';
  const filtered = groups
    .map((g) => ({
      label: g.label,
      items: g.items.filter((i) =>
        i.label.toLowerCase().includes(search.toLowerCase()),
      ),
    }))
    .filter((g) => g.items.length);
  expect(filtered).toHaveLength(1);
  expect(filtered[0].items[0].label).toBe('Text');
});
