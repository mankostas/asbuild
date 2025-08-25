import { describe, it, expect } from 'vitest';
import { filterAppointments } from '@/utils/appointmentFilters';

describe('filterAppointments', () => {
  const data = [
    { title: 'A', status: 'draft' },
    { title: 'B', status: 'scheduled' },
    { title: 'C', status: 'completed' },
  ];

  it('filters by global search', () => {
    const result = filterAppointments(data, { global: 'b' });
    expect(result).toHaveLength(1);
    expect(result[0].title).toBe('B');
  });

  it('filters by title', () => {
    const result = filterAppointments(data, { title: 'c' });
    expect(result).toHaveLength(1);
    expect(result[0].title).toBe('C');
  });

  it('filters by multiple statuses', () => {
    const result = filterAppointments(data, { status: ['scheduled', 'completed'] });
    expect(result).toHaveLength(2);
  });
});
