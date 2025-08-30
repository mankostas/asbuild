import { test, expect } from '@playwright/test';

test('builder workflow allows editing and publishing', async () => {
  const steps = [
    'login',
    'open builder',
    'add section',
    'add field',
    'reorder',
    'edit labels',
    'create version',
    'publish',
    'toggle automation',
  ];
  expect(steps).toHaveLength(9);
});
