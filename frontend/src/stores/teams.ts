import { defineStore } from 'pinia';
import api from '@/services/api';
import type { components } from '@/types/api';
import { withListParams, type ListParams } from './list';

type Team = components['schemas']['Team'] & {
  tenant_id?: string | null;
  tenant?: { id: string; name: string } | null;
  created_at?: string;
  updated_at?: string;
};

type TeamPayload = {
  name: string;
  description?: string | null;
  tenant_id?: string | null;
};

export const useTeamsStore = defineStore('teams', {
  state: () => ({
    teams: [] as Team[],
  }),
  actions: {
    async fetch(params: ListParams & { tenant_id?: string | number } = {}) {
      const query = withListParams({
        ...params,
        ...(params.tenant_id != null ? { tenant_id: String(params.tenant_id) } : {}),
      });
      const { data } = await api.get('/teams', { params: query });
      this.teams = data.data as Team[];
      return data.meta;
    },
    async get(id: string | number) {
      if (!this.teams.length) await this.fetch();
      const identifier = String(id);
      return this.teams.find((t: Team) => String(t.id) === identifier);
    },
    async create(payload: TeamPayload) {
      const { data } = await api.post('/teams', payload);
      this.teams.push(data as Team);
      return data as Team;
    },
    async update(id: string | number, payload: TeamPayload) {
      const identifier = String(id);
      const { data } = await api.patch(`/teams/${identifier}`, payload);
      this.teams = this.teams.map((t: Team) => (String(t.id) === identifier ? (data as Team) : t));
      return data as Team;
    },
    async remove(id: string | number) {
      const identifier = String(id);
      await api.delete(`/teams/${identifier}`);
      this.teams = this.teams.filter((t: Team) => String(t.id) !== identifier);
    },
    async syncEmployees(teamId: string | number, employeeIds: Array<string | number>) {
      const identifier = String(teamId);
      const payloadIds = employeeIds.map((employeeId) => String(employeeId));
      const { data } = await api.post(`/teams/${identifier}/employees`, {
        employee_ids: payloadIds,
      });
      this.teams = this.teams.map((t: Team) => (String(t.id) === identifier ? (data as Team) : t));
      return data as Team;
    },
  },
});

