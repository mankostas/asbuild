import { defineStore } from 'pinia';
import api from '@/services/api';
import type { components } from '@/types/api';
import { withListParams, type ListParams } from './list';

type Team = components['schemas']['Team'] & {
  tenant_id?: number | null;
  tenant?: { id: number; name: string } | null;
  created_at?: string;
  updated_at?: string;
};

type TeamPayload = {
  name: string;
  description?: string | null;
  tenant_id?: number | null;
};

export const useTeamsStore = defineStore('teams', {
  state: () => ({
    teams: [] as Team[],
  }),
  actions: {
    async fetch(params: ListParams & { tenant_id?: string | number } = {}) {
      const { data } = await api.get('/teams', { params: withListParams(params) });
      this.teams = data.data as Team[];
      return data.meta;
    },
    async get(id: number) {
      if (!this.teams.length) await this.fetch();
      return this.teams.find((t: Team) => t.id == id);
    },
    async create(payload: TeamPayload) {
      const { data } = await api.post('/teams', payload);
      this.teams.push(data as Team);
      return data as Team;
    },
    async update(id: number, payload: TeamPayload) {
      const { data } = await api.patch(`/teams/${id}`, payload);
      this.teams = this.teams.map((t: Team) => (t.id === id ? (data as Team) : t));
      return data as Team;
    },
    async remove(id: number) {
      await api.delete(`/teams/${id}`);
      this.teams = this.teams.filter((t: Team) => t.id !== id);
    },
    async syncEmployees(teamId: number, employeeIds: number[]) {
      const { data } = await api.post(`/teams/${teamId}/employees`, {
        employee_ids: employeeIds,
      });
      this.teams = this.teams.map((t: Team) => (t.id === teamId ? (data as Team) : t));
      return data as Team;
    },
  },
});

