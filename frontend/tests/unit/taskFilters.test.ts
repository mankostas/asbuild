import { describe, it, expect } from 'vitest';
import { filterTasks } from '@/utils/taskFilters';

describe('filterTasks', () => {
  const data = [
    { title: 'A', status: 'draft' },
    { title: 'B', status: 'scheduled' },
    { title: 'C', status: 'completed' },
  ];

  it('filters by global search', () => {
    const result = filterTasks(data, { global: 'b' });
    expect(result).toHaveLength(1);
    expect(result[0].title).toBe('B');
  });

  it('filters by title', () => {
    const result = filterTasks(data, { title: 'c' });
    expect(result).toHaveLength(1);
    expect(result[0].title).toBe('C');
  });

  it('filters by multiple statuses', () => {
    const result = filterTasks(data, { status: ['scheduled', 'completed'] });
    expect(result).toHaveLength(2);
  });
});
