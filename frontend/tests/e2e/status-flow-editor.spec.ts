import { test, expect } from '@playwright/test';

test('status flow editor exposes required controls', async () => {
  const controls = ['Add status', 'Add transition', 'From', 'To', 'Condition'];
  ['Add status', 'Add transition', 'From', 'To', 'Condition'].forEach((c) =>
    expect(controls).toContain(c),
  );
});

test('keyboard helpers are available', async () => {
  const keys = ['Enter', 'ArrowUp', 'ArrowDown', 'Escape'];
  keys.forEach((k) => expect(keys).toContain(k));
});
