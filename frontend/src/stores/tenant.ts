import { defineStore } from 'pinia';
import api from '@/services/api';
import { TENANT_ID_KEY } from '@/config/app';
import { withListParams, type ListParams } from './list';

const initialTenant = localStorage.getItem(TENANT_ID_KEY) || '';

export const useTenantStore = defineStore('tenant', {
  state: () => ({
    currentTenantId: initialTenant as string,
    tenants: [] as any[],
  }),
  getters: {
    tenantId: (state) => state.currentTenantId,
  },
  actions: {
    async loadTenants(params: ListParams = {}) {
      const { data } = await api.get('/tenants', {
        params: withListParams(params),
      });
      this.tenants = data.data;
      return data.meta;
    },
    setTenant(id: string | number) {
      const normalized = id ? String(id) : '';
      const changed = this.currentTenantId !== normalized;
      this.currentTenantId = normalized;
      if (normalized) {
        localStorage.setItem(TENANT_ID_KEY, normalized);
      } else {
        localStorage.removeItem(TENANT_ID_KEY);
      }
      if (changed && typeof window !== 'undefined') {
        window.location.reload();
      }
    },
  },
});
