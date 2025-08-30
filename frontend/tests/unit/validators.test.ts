import { describe, it, expect } from 'vitest';
import { validate } from '../../src/utils/validators';

describe('validators', () => {
  it('validates required fields', () => {
    expect(validate('', { required: true })).toBe('Required');
    expect(validate('value', { required: true })).toBeNull();
  });

  it('validates regex patterns', () => {
    expect(validate('abc', { regex: '^a.c$' })).toBeNull();
    expect(validate('ab', { regex: '^a.c$' })).toBe('Invalid format');
  });

  it('validates numeric ranges', () => {
    expect(validate(5, { min: 3, max: 10 })).toBeNull();
    expect(validate(2, { min: 3 })).toBe('Min 3');
    expect(validate(20, { max: 10 })).toBe('Max 10');
  });

  it('validates string length ranges', () => {
    expect(validate('hello', { lengthMin: 3, lengthMax: 10 })).toBeNull();
    expect(validate('hi', { lengthMin: 3 })).toBe('Min length 3');
    expect(validate('this is long', { lengthMax: 5 })).toBe('Max length 5');
  });

  it('validates file mime types and size', () => {
    const file = { mime: 'image/png', size: 500 };
    expect(validate(file, { mime: ['image/png'], size: 1000 })).toBeNull();
    expect(validate(file, { mime: ['application/pdf'] })).toBe('Invalid file type');
    expect(validate(file, { size: 100 })).toBe('File too large');
  });
});
