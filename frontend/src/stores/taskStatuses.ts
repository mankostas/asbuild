import { defineStore } from 'pinia';
import api from '@/services/api';
import { withListParams, type ListParams } from './list';

export const useTaskStatusesStore = defineStore('taskStatuses', {
  actions: {
    async fetch(
      scopeOrParams:
        | 'tenant'
        | 'global'
        | 'all'
        | (ListParams & {
            scope: 'tenant' | 'global' | 'all';
            tenant_id?: string | number;
          }),
      tenantId?: string | number,
      params: ListParams = {},
    ) {
      let query: any;
      if (typeof scopeOrParams === 'string') {
        query = withListParams({ scope: scopeOrParams, ...params });
        if (tenantId) query.tenant_id = String(tenantId);
      } else {
        query = withListParams({
          ...scopeOrParams,
          ...(scopeOrParams.tenant_id != null
            ? { tenant_id: String(scopeOrParams.tenant_id) }
            : {}),
        });
      }
      const { data } = await api.get('/task-statuses', { params: query });
      return data;
    },
    async fetchTransitions(id: string | number) {
      const { data } = await api.get(`/task-statuses/${String(id)}/transitions`);
      return data;
    },
    async copyToTenant(id: string | number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = String(tenantId);
      const { data } = await api.post(`/task-statuses/${String(id)}/copy-to-tenant`, payload);
      return data;
    },
    async copyManyToTenant(ids: Array<string | number>, tenantId?: string | number) {
      for (const id of ids) {
        await this.copyToTenant(id, tenantId);
      }
    },
    async deleteMany(ids: Array<string | number>) {
      await Promise.all(ids.map((id) => api.delete(`/task-statuses/${String(id)}`)));
    },
  },
});
