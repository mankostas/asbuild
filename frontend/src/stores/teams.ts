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

function normalizeTeam<T extends { id: string | number; tenant_id?: string | number | null; tenant?: { id?: string | number } | null }>(
  team: T,
): T {
  const normalized: T = {
    ...team,
    id: String(team.id),
  };
  if ('tenant_id' in normalized) {
    const tenantId = (normalized as any).tenant_id;
    (normalized as any).tenant_id = tenantId === null || tenantId === undefined ? null : String(tenantId);
  }
  if (normalized.tenant) {
    normalized.tenant = {
      ...normalized.tenant,
      id:
        normalized.tenant?.id === null || normalized.tenant?.id === undefined
          ? normalized.tenant?.id
          : String(normalized.tenant.id),
    } as any;
  }
  return normalized;
}

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
      this.teams = (data.data as Team[]).map((team: Team) => normalizeTeam(team) as Team);
      return data.meta;
    },
    async get(id: string | number) {
      if (!this.teams.length) await this.fetch();
      const identifier = String(id);
      return this.teams.find((t: Team) => String(t.id) === identifier);
    },
    async create(payload: TeamPayload) {
      const { data } = await api.post('/teams', payload);
      const normalized = normalizeTeam(data);
      this.teams.push(normalized as Team);
      return normalized as Team;
    },
    async update(id: string | number, payload: TeamPayload) {
      const identifier = String(id);
      const { data } = await api.patch(`/teams/${identifier}`, payload);
      const normalized = normalizeTeam(data);
      this.teams = this.teams.map((t: Team) => (String(t.id) === identifier ? (normalized as Team) : t));
      return normalized as Team;
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
      const normalized = normalizeTeam(data);
      this.teams = this.teams.map((t: Team) => (String(t.id) === identifier ? (normalized as Team) : t));
      return normalized as Team;
    },
  },
});

