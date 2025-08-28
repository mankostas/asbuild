import { defineStore } from 'pinia';
import api from '@/services/api';

interface FetchParams {
  scope?: string;
  tenantId?: string;
}

interface AssignPayload {
  roleId: number;
  userId: number;
  tenantId?: string;
}

export const useRolesStore = defineStore('roles', {
  state: () => ({
    roles: [] as any[],
  }),
  actions: {
    async fetch(params: FetchParams = {}) {
      const { scope, tenantId } = params;
      const { data } = await api.get('/roles', {
        params: {
          scope,
          tenant_id: tenantId,
        },
      });
      this.roles = data;
    },
    async create(payload: any) {
      const { data } = await api.post('/roles', payload);
      return data;
    },
    async update(id: number, payload: any) {
      const { data } = await api.patch(`/roles/${id}`, payload);
      return data;
    },
    async remove(id: number) {
      await api.delete(`/roles/${id}`);
      this.roles = this.roles.filter((r: any) => r.id !== id);
    },
    async assignUser(payload: AssignPayload) {
      const { roleId, userId, tenantId } = payload;
      await api.post(`/roles/${roleId}/assign`, {
        user_id: userId,
        tenant_id: tenantId,
      });
    },
  },
});

