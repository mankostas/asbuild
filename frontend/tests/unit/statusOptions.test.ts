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

  it('parses stringified status_flow_json', () => {
    const type2 = {
      ...type,
      status_flow_json: JSON.stringify([
        ['draft', 'assigned'],
        ['assigned', 'in_progress'],
        ['in_progress', 'in_review'],
        ['in_review', 'redo'],
      ]),
    };
    const opts = computeStatusOptions(type2, statusBySlug, false);
    expect(opts.map((o) => o.value)).toEqual(['draft', 'assigned']);
  });

  it('handles object form with single transitions', () => {
    const type3 = {
      ...type,
      status_flow_json: {
        draft: 'assigned',
        assigned: ['in_progress'],
        in_progress: 'in_review',
        in_review: 'redo',
      },
    };
    const opts = computeStatusOptions(type3, statusBySlug, false);
    expect(opts.map((o) => o.value)).toEqual(['draft', 'assigned']);
  });

  it('returns all statuses when flow is missing', () => {
    const type4 = { ...type, status_flow_json: null };
    const opts = computeStatusOptions(type4, statusBySlug, false);
    expect(opts.map((o) => o.value)).toEqual([
      'redo',
      'draft',
      'assigned',
      'in_progress',
      'in_review',
    ]);
  });

  it('skips statuses without transitions when choosing initial', () => {
    const type5 = {
      statuses: [
        { slug: 'redo' },
        { slug: 'draft' },
        { slug: 'assigned' },
        { slug: 'in_progress' },
      ],
      status_flow_json: [
        ['draft', 'assigned'],
        ['assigned', 'in_progress'],
      ],
    } as any;
    const opts = computeStatusOptions(type5, statusBySlug, false);
    expect(opts.map((o) => o.value)).toEqual(['draft', 'assigned']);
  });
});
