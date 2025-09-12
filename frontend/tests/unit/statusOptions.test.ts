import { describe, it, expect } from 'vitest';
import { computeStatusOptions } from '@/views/tasks/statusOptions';

describe('computeStatusOptions', () => {
  const statusBySlug = {
    redo: { name: 'Redo' },
    draft: { name: 'Draft' },
    assigned: { name: 'Assigned' },
    in_progress: { name: 'In Progress' },
    in_review: { name: 'In Review' },
  };
  const type: any = {
    statuses: [
      { slug: 'redo' },
      { slug: 'draft' },
      { slug: 'assigned' },
      { slug: 'in_progress' },
      { slug: 'in_review' },
    ],
    status_flow_json: [
      ['draft', 'assigned'],
      ['assigned', 'in_progress'],
      ['in_progress', 'in_review'],
      ['in_review', 'redo'],
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
