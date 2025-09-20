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
      const qp = withListParams({ ...params });
      if (qp.tenant_id != null) {
        qp.tenant_id = String(qp.tenant_id);
      }
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
    async update(id: string | number, payload: RolePayload) {
      const identifier = String(id);
      const { data } = await api.patch(`/roles/${identifier}`, payload);
      return data as Role;
    },
    async remove(id: string | number) {
      const identifier = String(id);
      await api.delete(`/roles/${identifier}`);
      this.roles = this.roles.filter((r: Role) => String(r.id) !== identifier);
    },
    async deleteMany(ids: Array<string | number>) {
      const identifiers = ids.map((id) => String(id));
      await Promise.all(identifiers.map((id) => api.delete(`/roles/${id}`)));
      this.roles = this.roles.filter((r: Role) => !identifiers.includes(String(r.id)));
    },
    async assignUser(roleId: string | number, payload: AssignPayload) {
      await api.post(`/roles/${String(roleId)}/assign`, payload);
    },
    async copyToTenant(id: string | number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = String(tenantId);
      const { data } = await api.post(`/roles/${String(id)}/copy-to-tenant`, payload);
      return data as Role;
    },
    async copyManyToTenant(ids: Array<string | number>, tenantId?: string | number) {
      for (const id of ids) {
        await this.copyToTenant(id, tenantId);
      }
    },
  },
});

