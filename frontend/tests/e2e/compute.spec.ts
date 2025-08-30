import { test, expect } from '@playwright/test';
import { evaluate } from '../../src/utils/compute';

test('evaluate expression', async () => {
  expect(evaluate('a + b', { a: 2, b: 3 })).toBe(5);
});
