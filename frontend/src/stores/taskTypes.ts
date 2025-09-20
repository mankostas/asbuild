import { defineStore } from 'pinia';
import api from '@/services/api';
import { withListParams, type ListParams } from './list';

export const useTaskTypesStore = defineStore('taskTypes', {
  actions: {
    async fetch(
      scope: 'tenant' | 'global' | 'all',
      tenantId?: string | number,
      params: ListParams = {},
    ) {
      const query: any = withListParams({ scope, ...params });
      if (tenantId) query.tenant_id = String(tenantId);
      const { data } = await api.get('/task-types', { params: query });
      return data;
    },
    async copyToTenant(id: string | number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = String(tenantId);
      const { data } = await api.post(`/task-types/${String(id)}/copy-to-tenant`, payload);
      return data;
    },
    async copyManyToTenant(ids: Array<string | number>, tenantId?: string | number) {
      const payload: any = { ids: ids.map((value) => String(value)) };
      if (tenantId) payload.tenant_id = String(tenantId);
      const { data } = await api.post('/task-types/bulk-copy-to-tenant', payload);
      return data;
    },
    async deleteMany(ids: Array<string | number>) {
      const { data } = await api.post('/task-types/bulk-delete', {
        ids: ids.map((value) => String(value)),
      });
      return data;
    },
    async export(id: string | number) {
      const { data } = await api.post(`/task-types/${String(id)}/export`);
      return data;
    },
    async import(payload: any) {
      const { data } = await api.post('/task-types/import', payload);
      return data;
    },
  },
});
