import { defineStore } from 'pinia';
import axios from 'axios';
import http from '../lib/http';
import {
  API_BASE_URL,
  ACCESS_TOKEN_KEY,
  REFRESH_TOKEN_KEY,
  USER_KEY,
} from '../config/app';

function readStorage() {
  const token = localStorage.getItem(ACCESS_TOKEN_KEY) || '';
  const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY) || '';
  let user = null;
  try {
    user = JSON.parse(localStorage.getItem(USER_KEY));
  } catch {}
  return { token, refreshToken, user };
}

const initial = readStorage();

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: initial.token,
    refreshToken: initial.refreshToken,
    user: initial.user,
  }),
  actions: {
    loadFromStorage() {
      const data = readStorage();
      this.token = data.token;
      this.refreshToken = data.refreshToken;
      this.user = data.user;
    },
    async login(payload) {
      const { data } = await http.post('/auth/login', payload);
      this.token = data.accessToken;
      this.refreshToken = data.refreshToken;
      this.user = data.user;
      localStorage.setItem(ACCESS_TOKEN_KEY, this.token);
      localStorage.setItem(REFRESH_TOKEN_KEY, this.refreshToken);
      localStorage.setItem(USER_KEY, JSON.stringify(this.user));
    },
    async logout() {
      try {
        await http.post('/auth/logout');
      } catch (e) {}
      this.token = '';
      this.refreshToken = '';
      this.user = null;
      localStorage.removeItem(ACCESS_TOKEN_KEY);
      localStorage.removeItem(REFRESH_TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
    },
    async refresh() {
      if (!this.refreshToken) throw new Error('Missing refresh token');
      const { data } = await axios.post(`${API_BASE_URL}/auth/refresh`, {
        refreshToken: this.refreshToken,
      });
      this.token = data.accessToken;
      this.refreshToken = data.refreshToken || this.refreshToken;
      localStorage.setItem(ACCESS_TOKEN_KEY, this.token);
      localStorage.setItem(REFRESH_TOKEN_KEY, this.refreshToken);
    },
  },
});
