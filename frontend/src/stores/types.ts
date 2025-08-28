import { defineStore } from 'pinia';
import api from '@/services/api';

export const useTypesStore = defineStore('types', {
  actions: {
    async fetch(scope: 'tenant' | 'global' | 'all', tenantId?: string | number) {
      const params: any = { scope };
      if (tenantId) params.tenant_id = tenantId;
      const { data } = await api.get('/appointment-types', { params });
      return data;
    },
    async copyToTenant(id: number, tenantId?: string | number) {
      const payload: any = {};
      if (tenantId) payload.tenant_id = tenantId;
      const { data } = await api.post(`/appointment-types/${id}/copy-to-tenant`, payload);
      return data;
    },
  },
});
