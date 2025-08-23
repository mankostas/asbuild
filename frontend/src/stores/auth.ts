// @ts-nocheck
import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    accessToken: localStorage.getItem('access_token') || '',
    refreshToken: localStorage.getItem('refresh_token') || '',
  }),
  getters: {
    isAuthenticated: (state) => !!state.accessToken,
  },
  actions: {
    async login(payload) {
      const { data } = await api.post('/auth/login', payload);
      if (data.access_token) {
        this.accessToken = data.access_token;
        this.refreshToken = data.refresh_token;
        this.user = data.user;
        localStorage.setItem('access_token', data.access_token);
        localStorage.setItem('refresh_token', data.refresh_token);
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
      localStorage.removeItem('access_token');
      localStorage.removeItem('refresh_token');
      delete api.defaults.headers.common['Authorization'];
    },
    async refresh() {
      if (!this.refreshToken) return;
      const { data } = await api.post('/auth/refresh', {
        refresh_token: this.refreshToken,
      });
      this.accessToken = data.access_token;
      this.refreshToken = data.refresh_token;
      localStorage.setItem('access_token', data.access_token);
      localStorage.setItem('refresh_token', data.refresh_token);
      api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
    },
    async requestPasswordReset(email) {
      await api.post('/auth/password/email', { email });
    },
    async resetPassword(payload) {
      await api.post('/auth/password/reset', payload);
    },
  },
});
