import { test, expect } from '@playwright/test';
import { resolveI18n } from '../../src/utils/i18n';

test('preview language toggle', async () => {
  const val = { en: 'Name', el: 'Όνομα' };
  expect(resolveI18n(val, 'el')).toBe('Όνομα');
  expect(resolveI18n(val, 'en')).toBe('Name');
});
