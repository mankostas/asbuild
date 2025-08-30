import { describe, it, expect } from 'vitest';
import { evaluate } from '../../src/utils/compute';

describe('compute evaluator', () => {
  it('evaluates arithmetic with field references', () => {
    const data = { a: 1, b: 2 };
    expect(evaluate('a + b * 3', data)).toBe(7);
  });
});
