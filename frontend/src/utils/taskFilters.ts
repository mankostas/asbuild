import type { components } from '@/types/api';

export type Task = components['schemas']['Task'];

export interface TaskFilters {
  global?: string | null;
  title?: string | null;
  status?: string[];
}

export function filterTasks(
  list: Task[],
  filters: TaskFilters,
): Task[] {
  const g = (filters.global || '').toLowerCase();
  const titleFilter = (filters.title || '').toLowerCase();
  const statusFilter = filters.status && filters.status.length ? filters.status : null;
  return list.filter((a) => {
    const title = (a.title || '').toLowerCase();
    const status = (a.status || '').toLowerCase();
    const matchesGlobal = g
      ? title.includes(g) || status.includes(g)
      : true;
    const matchesTitle = titleFilter ? title.includes(titleFilter) : true;
    const matchesStatus = statusFilter ? statusFilter.includes(a.status || '') : true;
    return matchesGlobal && matchesTitle && matchesStatus;
  });
}
