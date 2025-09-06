import { defineStore } from 'pinia';
import api from '@/services/api';
import { TENANT_ID_KEY } from '@/config/app';
import { withListParams, type ListParams } from './list';

const initialTenant = localStorage.getItem(TENANT_ID_KEY) || '';

export const useTenantStore = defineStore('tenant', {
  state: () => ({
    currentTenantId: initialTenant as string,
    tenants: [] as any[],
    allowedAbilities: {} as Record<string, Record<string, string[]>>,
  }),
  getters: {
    tenantId: (state) => state.currentTenantId,
    tenantAllowedAbilities: (state) =>
      (id: string | number) => state.allowedAbilities[String(id)] || {},
  },
  actions: {
    async loadTenants(params: ListParams = {}) {
      const { data } = await api.get('/tenants', {
        params: withListParams(params),
      });
      this.tenants = data.data;
      data.data.forEach((t: any) => {
        this.setAllowedAbilities(t.id, t.feature_abilities || {});
      });

      if (
        this.currentTenantId &&
        !this.tenants.some((t) => String(t.id) === this.currentTenantId)
      ) {
        this.setTenant('');
      }

      return data.meta;
    },
    async searchTenants(search: string) {
      return this.loadTenants({ search, per_page: 100 });
    },
    setTenantFeatures(id: string | number, features: string[]) {
      const tenant = this.tenants.find((t) => String(t.id) === String(id));
      if (tenant) tenant.features = features;
    },
    setAllowedAbilities(id: string | number, abilities: Record<string, string[]>) {
      this.allowedAbilities[String(id)] = JSON.parse(JSON.stringify(abilities));
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
      // The store no longer forces a hard page reload. Callers that need a
      // full refresh (such as impersonation) can check the return value and
      // decide whether to reload themselves.
      return changed;
    },
  },
});
