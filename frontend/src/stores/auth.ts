// @ts-nocheck
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

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    accessToken: initialAccess,
    refreshToken: initialRefresh,
    impersonator: null as any,
  }),
  getters: {
    isAuthenticated: (state) => !!state.accessToken,
    isImpersonating: (state) => !!state.impersonator,
  },
  actions: {
    async login(payload) {
      const { data } = await api.post('/auth/login', payload);
      if (data.access_token) {
        this.accessToken = data.access_token;
        this.refreshToken = data.refresh_token;
        this.user = data.user;
        setTokens(data.access_token, data.refresh_token);
        api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
      }
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
      this.impersonator = null;
    },
    async refresh() {
      if (!this.refreshToken) return;
      const { data } = await api.post('/auth/refresh', {
        refresh_token: this.refreshToken,
      });
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      setTokens(data.access_token, data.refresh_token);
      api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
    },
    async requestPasswordReset(email) {
      await api.post('/auth/password/email', { email });
    },
    async resetPassword(payload) {
      await api.post('/auth/password/reset', payload);
    },
    async impersonate(tenantId) {
      this.impersonator = {
        accessToken: this.accessToken,
        refreshToken: this.refreshToken,
        user: this.user,
      };
      const { data } = await api.post(`/tenants/${tenantId}/impersonate`);
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      this.user = data.user;
      setTokens(data.access_token, data.refresh_token);
      api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
    },
    stopImpersonation() {
      if (!this.impersonator) return;
      this.accessToken = this.impersonator.accessToken;
      this.refreshToken = this.impersonator.refreshToken;
      this.user = this.impersonator.user;
      setTokens(this.accessToken, this.refreshToken);
      api.defaults.headers.common['Authorization'] = `Bearer ${this.accessToken}`;
      this.impersonator = null;
    },
  },
});
