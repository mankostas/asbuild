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
      const { data } = await api.get('/branding');
      this.branding = data;
      this.applyTheme();
    },
    async update(payload: Record<string, any>) {
      const { data } = await api.put('/branding', payload);
      this.branding = data;
      this.applyTheme();
    },
    applyTheme() {
      const color = this.branding.color;
      if (color) {
        const hex = color.replace('#', '');
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        document.documentElement.style.setProperty(
          '--color-primary',
          `${r} ${g} ${b}`,
        );
      }
    },
  },
});
