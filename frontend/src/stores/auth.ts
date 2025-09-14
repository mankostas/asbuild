import { defineStore } from 'pinia';
import api, { registerAuthStore } from '@/services/api';
import { useThemeSettingsStore } from './themeSettings';
import { useTenantStore } from '@/stores/tenant';
import { TENANTS_KEY } from '@/config/app';
import {
  getAccessToken,
  getRefreshToken,
  setTokens,
  clearTokens,
} from '@/services/authStorage';

const initialAccess = getAccessToken();
const initialRefresh = getRefreshToken();
if (initialAccess) {
  api.defaults.headers.common['Authorization'] = `Bearer ${initialAccess}`;
}

interface LoginPayload {
  email: string;
  password: string;
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as any,
    accessToken: initialAccess as string | null,
    refreshToken: initialRefresh as string | null,
    impersonatedTenant: localStorage.getItem('impersonatingTenant') || '',
    impersonator: JSON.parse(localStorage.getItem('impersonator') || 'null') as any,
    abilities: [] as string[],
    features: [] as string[],
  }),
  getters: {
    isAuthenticated: (state) => !!state.accessToken,
    isImpersonating: (state) => !!state.impersonatedTenant,
    isSuperAdmin: (state) =>
      state.abilities.includes('*') ||
      state.user?.roles?.some(
        (r: any) => r.name === 'SuperAdmin' || r.slug === 'super_admin',
      ) || false,
    can(state) {
      return (ability: string) =>
        this.isSuperAdmin ||
        state.abilities.includes('*') ||
        state.abilities.includes(ability);
    },
    hasAny: (state) => (abilities: string[]) =>
      abilities.length === 0 ||
      state.abilities.includes('*') ||
      state.user?.roles?.some(
        (r: any) => r.name === 'SuperAdmin' || r.slug === 'super_admin',
      ) ||
      abilities.some((a) => state.abilities.includes(a)),
    userId: (state) => state.user?.id,
  },
  actions: {
    async login(payload: LoginPayload) {
      const { data } = await api.post('/auth/login', payload);
      if (data.access_token) {
        this.accessToken = data.access_token;
        this.refreshToken = data.refresh_token;
        setTokens(data.access_token, data.refresh_token);
        api.defaults.headers.common['Authorization'] =
          `Bearer ${data.access_token}`;
        await this.fetchUser();
        await useThemeSettingsStore().load();
      }
    },
    async fetchUser() {
      const { data } = await api.get('/me');
      this.user = data.user;
      this.abilities = data.abilities || [];
      this.features = data.features || [];
      const tenantStore = useTenantStore();
      const tenantId =
        data.user?.tenant_id || (this.isSuperAdmin ? 'super_admin' : '');
      tenantStore.setTenant(tenantId);
      if (this.isSuperAdmin || this.isImpersonating) {
        await tenantStore.loadTenants();
      }
    },
    async logout(skipServer = false) {
      if (!skipServer) {
        try {
          await api.post('/auth/logout');
        } catch (e) {}
      }
      this.accessToken = '';
      this.refreshToken = '';
      this.user = null;
      this.abilities = [];
      clearTokens();
      delete api.defaults.headers.common['Authorization'];
      this.impersonatedTenant = '';
      localStorage.removeItem('impersonatingTenant');
      this.impersonator = null;
      localStorage.removeItem('impersonator');
      localStorage.removeItem(TENANTS_KEY);
      const tenantStore = useTenantStore();
      tenantStore.setTenant('');
      tenantStore.tenants = [];
    },
    async refresh() {
      if (!this.refreshToken) return;
      const { data } = await api.post('/auth/refresh', {
        refresh_token: this.refreshToken,
      });
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      setTokens(data.access_token, data.refresh_token);
      api.defaults.headers.common['Authorization'] =
        `Bearer ${data.access_token}`;
    },
    async requestPasswordReset(email: string) {
      await api.post('/auth/password/email', { email });
    },
    async resetPassword(payload: Record<string, any>) {
      await api.post('/auth/password/reset', payload);
    },
    async impersonate(tenantId: string, tenantName: string) {
      localStorage.setItem('impersonator', JSON.stringify(this.user));
      this.impersonator = this.user;
      const { data } = await api.post(`/tenants/${tenantId}/impersonate`);
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      setTokens(data.access_token, data.refresh_token);
      api.defaults.headers.common['Authorization'] =
        `Bearer ${data.access_token}`;
      this.impersonatedTenant = tenantName;
      localStorage.setItem('impersonatingTenant', tenantName);
      await this.fetchUser();
    },
  },
});

export function can(ability: string): boolean {
  return useAuthStore().can(ability);
}

export function hasAny(abilities: string[]): boolean {
  return useAuthStore().hasAny(abilities);
}

export function hasFeature(feature: string): boolean {
  return useAuthStore().features.includes(feature);
}

export function userId(): string | number | undefined {
  return useAuthStore().userId;
}

registerAuthStore(() => useAuthStore());
