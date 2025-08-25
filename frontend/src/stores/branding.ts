import { defineStore } from 'pinia';
import api from '@/services/api';

export const useBrandingStore = defineStore('branding', {
  state: () => ({
    branding: { name: '', color: '', logo: '', email_from: '' } as Record<
      string,
      any
    >,
  }),
  actions: {
    async load() {
      const { data } = await api.get('/settings/branding');
      this.branding = data;
    },
    async update(payload: Record<string, any>) {
      const { data } = await api.put('/settings/branding', payload);
      this.branding = data;
    },
  },
});
