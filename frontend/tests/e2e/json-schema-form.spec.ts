import { test, expect } from '@playwright/test';
import { evaluateLogic } from '../../src/utils/logic';

test('conditional logic evaluates visibility and requiredness', async () => {
  const schema = {
    sections: [],
    logic: [
      {
        if: { field: 'priority', eq: 'high' },
        then: [
          { require: 'due_date' },
          { show: 'escalation_reason' },
        ],
      },
    ],
  };

  const high = evaluateLogic(schema, { priority: 'high' });
  expect(high.visible.has('escalation_reason')).toBe(true);
  expect(high.required.has('due_date')).toBe(true);

  const low = evaluateLogic(schema, { priority: 'low' });
  expect(low.visible.has('escalation_reason')).toBe(false);
  expect(low.required.has('due_date')).toBe(false);
});
