import { defineStore } from 'pinia';
import api from '@/services/api';
import { TENANT_ID_KEY, TENANTS_KEY } from '@/config/app';
import { withListParams, type ListParams } from './list';
import { useLookupsStore } from '@/stores/lookups';

const initialTenant = localStorage.getItem(TENANT_ID_KEY) || '';
const initialTenants = JSON.parse(localStorage.getItem(TENANTS_KEY) || '[]');

export const useTenantStore = defineStore('tenant', {
  state: () => ({
    currentTenantId: initialTenant as string,
    tenants: initialTenants as any[],
    allowedAbilities: {} as Record<string, Record<string, string[]>>,
  }),
  getters: {
    tenantId: (state) => state.currentTenantId,
    tenantAllowedAbilities: (state) =>
      (id: string | number) => state.allowedAbilities[String(id)] || {},
  },
  actions: {
    async loadTenants(params: ListParams = {}) {
      try {
        const { data } = await api.get('/tenants', {
          params: withListParams({ per_page: 100, ...params }),
        });
        this.tenants = data.data;

        try {
          const { useAuthStore } = await import('@/stores/auth');
          const auth = useAuthStore();
          if (
            auth.user &&
            auth.isSuperAdmin &&
            !this.tenants.some((t: any) => String(t.id) === String(auth.user?.tenant_id))
          ) {
            this.tenants.unshift({
              id: auth.user.tenant_id,
              name: auth.user.name,
              phone: auth.user.phone ?? '',
              address: auth.user.address ?? '',
            });
          }
        } catch (e) {
          // Ignore errors when auth store is unavailable (e.g., during tests)
        }

        localStorage.setItem(TENANTS_KEY, JSON.stringify(this.tenants));
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
      } catch (error: any) {
        if (error?.status === 403) {
          // Preserve the current tenant and previously loaded tenants when the
          // impersonated user lacks permission to list tenants. Clearing the
          // state here would drop the active tenant and break impersonation.
          return { total: this.tenants.length } as any;
        }
        throw error;
      }
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
      if (changed) {
        const lookups = useLookupsStore();
        lookups.$reset();
      }
      // The store no longer forces a hard page reload. Callers that need a
      // full refresh (such as impersonation) can check the return value and
      // decide whether to reload themselves.
      return changed;
    },
  },
});
