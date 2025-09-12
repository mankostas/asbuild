import { describe, it, expect } from 'vitest';
import { computeStatusOptions } from '@/views/tasks/statusOptions';

describe('computeStatusOptions', () => {
  const statusBySlug = {
    draft: { name: 'Draft' },
    assigned: { name: 'Assigned' },
    in_progress: { name: 'In Progress' },
  };
  const type: any = {
    statuses: [
      { slug: 'draft' },
      { slug: 'assigned' },
      { slug: 'in_progress' },
    ],
    status_flow_json: [
      ['draft', 'assigned'],
      ['assigned', 'in_progress'],
    ],
  };

  it('limits create options to initial transitions', () => {
    const opts = computeStatusOptions(type, statusBySlug, false);
    expect(opts.map((o) => o.value)).toEqual(['draft', 'assigned']);
  });

  it('limits edit options to current transitions', () => {
    const opts = computeStatusOptions(type, statusBySlug, true, 'assigned');
    expect(opts.map((o) => o.value)).toEqual(['assigned', 'in_progress']);
  });
});
