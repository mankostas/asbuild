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
    async fetchAssignees(
      type: 'all' | 'teams' | 'employees' = 'all',
      force = false,
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

      const { data } = await api.get('/lookups/assignees', { params: withListParams({ type }) });

      if (type === 'all') {
        this.assignees.teams = data.filter((a: any) => a.kind === 'team');
        this.assignees.employees = data.filter((a: any) => a.kind === 'employee');
        this.assigneeFetchedAt.teams = now;
        this.assigneeFetchedAt.employees = now;
      } else {
        // @ts-ignore
        this.assignees[type] = data;
        this.assigneeFetchedAt[type] = now;
      }

      return data;
    },
  },
});

