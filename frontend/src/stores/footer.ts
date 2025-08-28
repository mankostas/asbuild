import { defineStore } from 'pinia';
import api from '@/services/api';

export const useFooterStore = defineStore('footer', {
  state: () => ({
    left: '' as string,
    right: '' as string,
  }),
  actions: {
    async load() {
      const { data } = await api.get('/branding');
      this.left = data.footer_left || '';
      this.right = data.footer_right || '';
    },
    async update(left: string, right: string) {
      const { data } = await api.put('/branding', {
        footer_left: left,
        footer_right: right,
      });
      this.left = data.footer_left || '';
      this.right = data.footer_right || '';
    },
  },
});
