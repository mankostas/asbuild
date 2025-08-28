import { defineStore } from 'pinia';
import api from '@/services/api';

interface TeamPayload {
  name: string;
  description?: string;
}

export const useTeamsStore = defineStore('teams', {
  state: () => ({
    teams: [] as any[],
  }),
  actions: {
    async fetch() {
      const { data } = await api.get('/teams');
      this.teams = data;
    },
    async get(id: number) {
      if (!this.teams.length) await this.fetch();
      return this.teams.find((t: any) => t.id == id);
    },
    async create(payload: TeamPayload) {
      const { data } = await api.post('/teams', payload);
      this.teams.push(data);
      return data;
    },
    async update(id: number, payload: TeamPayload) {
      const { data } = await api.patch(`/teams/${id}`, payload);
      this.teams = this.teams.map((t: any) => (t.id === id ? data : t));
      return data;
    },
    async remove(id: number) {
      await api.delete(`/teams/${id}`);
      this.teams = this.teams.filter((t: any) => t.id !== id);
    },
    async syncEmployees(teamId: number, employeeIds: number[]) {
      const { data } = await api.post(`/teams/${teamId}/employees`, {
        employee_ids: employeeIds,
      });
      this.teams = this.teams.map((t: any) => (t.id === teamId ? data : t));
      return data;
    },
  },
});

