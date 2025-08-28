import { defineStore } from 'pinia';
import api from '@/services/api';

export const useLookupsStore = defineStore('lookups', {
  state: () => ({
    assignees: {
      teams: [] as any[],
      employees: [] as any[],
    },
  }),
  actions: {
    async fetchAssignees(type: 'all' | 'teams' | 'employees' = 'all') {
      const { data } = await api.get('/lookups/assignees', { params: { type } });
      if (type === 'all') {
        this.assignees.teams = data.filter((a: any) => a.kind === 'team');
        this.assignees.employees = data.filter((a: any) => a.kind === 'employee');
      } else {
        // @ts-ignore
        this.assignees[type] = data;
      }
      return data;
    },
  },
});

