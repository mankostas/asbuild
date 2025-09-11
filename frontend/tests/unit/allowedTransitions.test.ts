import { describe, it, expect } from 'vitest';
import { computeAllowedTransitions } from '@/views/tasks/allowedTransitions';

describe('computeAllowedTransitions', () => {
  const columns = [{ status: { slug: 'assigned' } }, { status: { slug: 'in_progress' } }];

  it('handles array form of status_flow_json', () => {
    const task: any = {
      status_slug: 'draft',
      type: {
        status_flow_json: [
          ['draft', 'assigned'],
          ['draft', 'in_progress'],
        ],
      },
    };
    const result = computeAllowedTransitions(task, 'draft', false, columns);
    expect(result.sort()).toEqual(['assigned', 'in_progress']);
  });

  it('handles object form of status_flow_json', () => {
    const task: any = {
      status_slug: 'draft',
      type: {
        status_flow_json: {
          draft: ['assigned', 'in_progress'],
        },
      },
    };
    const result = computeAllowedTransitions(task, 'draft', false, columns);
    expect(result.sort()).toEqual(['assigned', 'in_progress']);
  });
});
