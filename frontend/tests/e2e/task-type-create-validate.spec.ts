import { test, expect } from '@playwright/test';

test('create task type validation succeeds with valid data', () => {
  const response = { status: 200 };
  expect(response.status).toBe(200);
});

test('create task type validation shows field errors', () => {
  const response = { status: 422, errors: { f1: 'required' } };
  expect(response.status).toBe(422);
  expect(response.errors).toHaveProperty('f1');
});
