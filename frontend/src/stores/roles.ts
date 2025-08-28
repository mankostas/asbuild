import { defineStore } from 'pinia';
import api from '@/services/api';
import type { components, paths } from '@/types/api';

type Role = components['schemas']['Role'];
type FetchParams = paths['/roles']['get']['parameters']['query'];
type AssignPayload = paths['/roles/{roleId}/assign']['post']['requestBody']['content']['application/json'];

export const useRolesStore = defineStore('roles', {
  state: () => ({
    roles: [] as Role[],
  }),
  actions: {
    async fetch(params: FetchParams = {}) {
      const { data } = await api.get('/roles', { params });
      this.roles = data as Role[];
    },
    async create(payload: Role) {
      const { data } = await api.post('/roles', payload);
      return data as Role;
    },
    async update(id: number, payload: Role) {
      const { data } = await api.patch(`/roles/${id}`, payload);
      return data as Role;
    },
    async remove(id: number) {
      await api.delete(`/roles/${id}`);
      this.roles = this.roles.filter((r: Role) => r.id !== id);
    },
    async assignUser(roleId: number, payload: AssignPayload) {
      await api.post(`/roles/${roleId}/assign`, payload);
    },
  },
});

