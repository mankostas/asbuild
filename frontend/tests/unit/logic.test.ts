import { describe, it, expect } from 'vitest';
import { evaluateLogic } from '../../src/utils/logic';

describe('conditional logic evaluation', () => {
  const schema = {
    logic: [
      {
        if: { field: 'status', eq: 'A' },
        then: [{ show: 'field1' }, { require: 'field2' }],
      },
    ],
  };

  it('returns visible and required sets when condition matches', () => {
    const result = evaluateLogic(schema, { status: 'A' });
    expect(Array.from(result.visible)).toEqual(['field1']);
    expect(Array.from(result.required)).toEqual(['field2']);
    expect(Array.from(result.showTargets)).toEqual(['field1']);
  });

  it('collects show targets even when condition does not match', () => {
    const result = evaluateLogic(schema, { status: 'B' });
    expect(Array.from(result.visible)).toEqual([]);
    expect(Array.from(result.required)).toEqual([]);
    expect(Array.from(result.showTargets)).toEqual(['field1']);
  });

  it('handles multiple rules', () => {
    const multiSchema = {
      logic: [
        { if: { field: 'status', eq: 'A' }, then: [{ show: 'field1' }] },
        { if: { field: 'type', eq: 1 }, then: [{ require: 'field3' }] },
      ],
    };
    const result = evaluateLogic(multiSchema, { status: 'C', type: 1 });
    expect(Array.from(result.visible)).toEqual([]);
    expect(Array.from(result.required)).toEqual(['field3']);
    expect(Array.from(result.showTargets)).toEqual(['field1']);
  });
});
