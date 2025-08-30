import { test, expect } from '@playwright/test';
import { validate } from '../../src/utils/validators';

test('validator utility enforces rules', async () => {
  expect(validate('', { required: true })).toBe('Required');
  expect(validate('abc', { regex: '^\\d+$' })).toBe('Invalid format');
  expect(validate(5, { min: 10 })).toBe('Min 10');
});
