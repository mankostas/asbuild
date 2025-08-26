import { defineStore } from 'pinia';
import api from '@/services/api';
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
  }),
  getters: {
    isAuthenticated: (state) => !!state.accessToken,
    isImpersonating: (state) => !!state.impersonatedTenant,
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
      }
    },
    async fetchUser() {
      const { data } = await api.get('/me');
      this.user = data;
    },
    async logout() {
      try {
        await api.post('/auth/logout');
      } catch (e) {}
      this.accessToken = '';
      this.refreshToken = '';
      this.user = null;
      clearTokens();
      delete api.defaults.headers.common['Authorization'];
      this.impersonatedTenant = '';
      localStorage.removeItem('impersonatingTenant');
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
      const { data } = await api.post(`/tenants/${tenantId}/impersonate`);
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      this.user = data.user;
      setTokens(data.access_token, data.refresh_token);
      api.defaults.headers.common['Authorization'] =
        `Bearer ${data.access_token}`;
      this.impersonatedTenant = tenantName;
      localStorage.setItem('impersonatingTenant', tenantName);
    },
  },
});
