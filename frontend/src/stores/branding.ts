import { defineStore } from 'pinia';
import api from '@/services/api';

export const useBrandingStore = defineStore('branding', {
  state: () => ({
    branding: {
      name: 'Asbuild SPA',
      color: '#4669fa',
      secondary_color: '#A0AEC0',
      logo: '',
      logo_dark: '',
      email_from: '',
    } as Record<string, any>,
  }),
  actions: {
    async load() {
      try {
        const { data } = await api.get('/branding');
        this.branding = { ...this.branding, ...data };
      } catch (_) {
        // Ignore errors so the app can still load without branding info
      }
      this.applyTheme();
    },
    async update(payload: Record<string, any>) {
      const { data } = await api.put('/branding', payload);
      this.branding = data;
      this.applyTheme();
    },
    applyTheme() {
      const setVar = (name: string, hex?: string) => {
        if (!hex) return;
        const clean = hex.replace('#', '');
        const r = parseInt(clean.slice(0, 2), 16);
        const g = parseInt(clean.slice(2, 4), 16);
        const b = parseInt(clean.slice(4, 6), 16);
        document.documentElement.style.setProperty(name, `${r} ${g} ${b}`);
      };
      setVar('--color-primary', this.branding.color);
      setVar('--color-secondary', this.branding.secondary_color);
      if (this.branding.name) {
        document.title = this.branding.name;
      }
    },
  },
});
