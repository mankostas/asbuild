import { defineStore } from 'pinia';
import api from '@/services/api';
import { withListParams } from './list';

export const useLookupsStore = defineStore('lookups', {
  state: () => ({
    assignees: {
      teams: [] as any[],
      employees: [] as any[],
    },
    assigneeFetchedAt: {
      teams: 0,
      employees: 0,
    },
  }),
  actions: {
    normalizeAssignee(record: any) {
      if (!record || typeof record !== 'object') {
        return record;
      }
      const normalized: any = { ...record };
      if (normalized.id !== undefined && normalized.id !== null) {
        normalized.id = String(normalized.id);
      }
      if (normalized.team_id !== undefined && normalized.team_id !== null) {
        normalized.team_id = String(normalized.team_id);
      }
      if (normalized.user_id !== undefined && normalized.user_id !== null) {
        normalized.user_id = String(normalized.user_id);
      }
      if (normalized.employee_id !== undefined && normalized.employee_id !== null) {
        normalized.employee_id = String(normalized.employee_id);
      }
      return normalized;
    },
    async fetchAssignees(
      type: 'all' | 'teams' | 'employees' = 'all',
      force = false,
      extraParams: Record<string, any> = {},
    ) {
      const ttl = 5 * 60 * 1000; // 5 minutes
      const now = Date.now();

      const cacheValid = (t: 'teams' | 'employees') =>
        !force && this.assignees[t].length && now - this.assigneeFetchedAt[t] < ttl;

      if (type === 'all') {
        if (cacheValid('teams') && cacheValid('employees')) {
          return [...this.assignees.teams, ...this.assignees.employees];
        }
      } else if (cacheValid(type)) {
        return this.assignees[type];
      }

      const params = withListParams({ type, ...extraParams });
      const { data } = await api.get('/lookups/assignees', { params });
      const normalized = Array.isArray(data)
        ? data.map((record: any) => this.normalizeAssignee(record))
        : [];

      if (type === 'all') {
        this.assignees.teams = normalized.filter((a: any) => a.kind === 'team');
        this.assignees.employees = normalized.filter((a: any) => a.kind === 'employee');
        this.assigneeFetchedAt.teams = now;
        this.assigneeFetchedAt.employees = now;
      } else {
        // @ts-ignore
        this.assignees[type] = normalized;
        this.assigneeFetchedAt[type] = now;
      }

      return normalized;
    },
  },
});

