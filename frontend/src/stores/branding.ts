// @ts-nocheck
import { defineStore } from 'pinia';
import api from '@/services/api';

export const useBrandingStore = defineStore('branding', {
  state: () => ({
    branding: { name: '', color: '', logo: '', email_from: '' },
  }),
  actions: {
    async load() {
      const { data } = await api.get('/settings/branding');
      this.branding = data;
    },
    async update(payload) {
      const { data } = await api.put('/settings/branding', payload);
      this.branding = data;
    },
  },
});
