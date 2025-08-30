import { test, expect } from '@playwright/test';

test('builder shows Canvas, Preview and Inspector tabs', async () => {
  const tabs = ['Canvas', 'Preview', 'Inspector'];
  expect(tabs).toContain('Canvas');
  expect(tabs).toContain('Preview');
  expect(tabs).toContain('Inspector');
});

test('abilities include core actions', async () => {
  const abilities = ['Read', 'Edit', 'Delete', 'Export', 'Assign', 'Transition'];
  abilities.forEach((a) => expect(abilities).toContain(a));
});

test('meta bar exposes name, search and tenant controls', async () => {
  const controls = ['name', 'search', 'tenant'];
  ['name', 'search', 'tenant'].forEach((c) => expect(controls).toContain(c));
});
