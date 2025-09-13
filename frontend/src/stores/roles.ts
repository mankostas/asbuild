import { defineStore } from 'pinia';
import api from '@/services/api';
import type { components, paths } from '@/types/api';
import { withListParams, type ListParams } from './list';

type Role = components['schemas']['Role'];
type FetchParams = paths['/roles']['get']['parameters']['query'];
type AssignPayload = paths['/roles/{roleId}/assign']['post']['requestBody']['content']['application/json'];
type RolePayload = Role & {
  slug?: string;
  abilities?: string[];
  tenant_id?: string | null;
  level?: number;
  description?: string | null;
};

export const useRolesStore = defineStore('roles', {
  state: () => ({
    roles: [] as Role[],
  }),
  actions: {
    async fetch(params: ListParams = {}) {
      const qp = withListParams(params);
      // When requesting all roles as a super admin we should not
      // send the tenant_id parameter. Providing it would instruct
      // the backend to limit the results to that tenant, preventing
      // cross-tenant visibility.
      if (qp.scope === 'all') {
        delete qp.tenant_id;
      }
      const { data } = await api.get('/roles', { params: qp });
      this.roles = data.data as Role[];
      return data.meta;
    },
    async create(payload: RolePayload) {
      const { data } = await api.post('/roles', payload);
      return data as Role;
    },
    async update(id: number, payload: RolePayload) {
      const { data } = await api.patch(`/roles/${id}`, payload);
      return data as Role;
    },
    async remove(id: number) {
      await api.delete(`/roles/${id}`);
      this.roles = this.roles.filter((r: Role) => r.id !== id);
    },
    async deleteMany(ids: number[]) {
      await Promise.all(ids.map((id) => api.delete(`/roles/${id}`)));
      this.roles = this.roles.filter((r: Role) => !ids.includes(r.id as number));
    },
    async assignUser(roleId: number, payload: AssignPayload) {
      await api.post(`/roles/${roleId}/assign`, payload);
    },
    async copyToTenant(id: number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = tenantId;
      const { data } = await api.post(`/roles/${id}/copy-to-tenant`, payload);
      return data as Role;
    },
    async copyManyToTenant(ids: number[], tenantId?: string | number) {
      for (const id of ids) {
        await this.copyToTenant(id, tenantId);
      }
    },
  },
});

