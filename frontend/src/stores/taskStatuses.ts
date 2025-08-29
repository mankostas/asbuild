import { defineStore } from 'pinia';
import api from '@/services/api';
import { withListParams, type ListParams } from './list';

export const useTaskStatusesStore = defineStore('taskStatuses', {
  actions: {
    async fetch(
      scope: 'tenant' | 'global' | 'all',
      tenantId?: string | number,
      params: ListParams = {},
    ) {
      const query: any = withListParams({ scope, ...params });
      if (tenantId) query.tenant_id = tenantId;
      const { data } = await api.get('/task-statuses', { params: query });
      return data;
    },
    async fetchTransitions(id: number) {
      const { data } = await api.get(`/task-statuses/${id}/transitions`);
      return data;
    },
    async copyToTenant(id: number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = tenantId;
      const { data } = await api.post(`/task-statuses/${id}/copy-to-tenant`, payload);
      return data;
    },
  },
});
