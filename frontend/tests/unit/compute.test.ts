import { describe, it, expect } from 'vitest';
import { evaluate } from '../../src/utils/compute';

describe('compute evaluator', () => {
  it('evaluates arithmetic with field references', () => {
    const data = { a: 1, b: 2 };
    expect(evaluate('a + b * 3', data)).toBe(7);
  });

  it('supports parentheses and nested fields', () => {
    const data = { a: 1, b: { c: 2 } };
    expect(evaluate('(a + b.c) * 2', data)).toBe(6);
  });

  it('treats unknown fields and division by zero as zero', () => {
    const data = { a: 5 };
    expect(evaluate('a / missing', data)).toBe(0);
  });
});
