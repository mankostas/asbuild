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
      if (tenantId) query.tenant_id = tenantId;
      const { data } = await api.get('/task-types', { params: query });
      return data;
    },
    async copyToTenant(id: number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = tenantId;
      const { data } = await api.post(`/task-types/${id}/copy-to-tenant`, payload);
      return data;
    },
    async export(id: number) {
      const { data } = await api.post(`/task-types/${id}/export`);
      return data;
    },
    async import(payload: any) {
      const { data } = await api.post('/task-types/import', payload);
      return data;
    },
  },
});
