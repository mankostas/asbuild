import type { components } from '@/types/api';

export type Appointment = components['schemas']['Appointment'];

export interface AppointmentFilters {
  global?: string | null;
  title?: string | null;
  status?: string[];
}

export function filterAppointments(
  list: Appointment[],
  filters: AppointmentFilters,
): Appointment[] {
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
