import { defineStore } from 'pinia';
import { TENANT_ID_KEY } from '../config/app';

const initialTenant = localStorage.getItem(TENANT_ID_KEY) || '';

export const useTenantStore = defineStore('tenant', {
  state: () => ({
    tenantId: initialTenant,
  }),
  actions: {
    setTenant(id) {
      this.tenantId = id;
      if (id) {
        localStorage.setItem(TENANT_ID_KEY, id);
      } else {
        localStorage.removeItem(TENANT_ID_KEY);
      }
    },
  },
});
