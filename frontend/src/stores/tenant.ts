import { defineStore } from 'pinia';
import api from '@/services/api';
import { TENANT_ID_KEY } from '@/config/app';

const initialTenant = localStorage.getItem(TENANT_ID_KEY) || '';

export const useTenantStore = defineStore('tenant', {
  state: () => ({
    currentTenantId: initialTenant as string,
    tenants: [] as any[],
  }),
  actions: {
    async loadTenants() {
      const { data } = await api.get('/tenants');
      this.tenants = data;
    },
    setTenant(id: string) {
      this.currentTenantId = id;
      if (id) {
        localStorage.setItem(TENANT_ID_KEY, id);
      } else {
        localStorage.removeItem(TENANT_ID_KEY);
      }
      if (typeof window !== 'undefined') {
        window.location.reload();
      }
    },
  },
});
