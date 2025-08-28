import { defineStore } from 'pinia';
import api from '@/services/api';

export const useStatusesStore = defineStore('statuses', {
  actions: {
    async fetch(scope: 'tenant' | 'global' | 'all', tenantId?: string | number) {
      const params: any = { scope };
      if (tenantId) params.tenant_id = tenantId;
      const { data } = await api.get('/statuses', { params });
      return data;
    },
    async fetchTransitions(id: number) {
      const { data } = await api.get(`/statuses/${id}/transitions`);
      return data;
    },
    async copyToTenant(id: number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = tenantId;
      const { data } = await api.post(`/statuses/${id}/copy-to-tenant`, payload);
      return data;
    },
  },
});
