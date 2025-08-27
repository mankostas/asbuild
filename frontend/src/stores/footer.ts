import { defineStore } from 'pinia';
import api from '@/services/api';

export const useFooterStore = defineStore('footer', {
  state: () => ({
    left: '' as string,
    right: '' as string,
  }),
  actions: {
    async load() {
      const { data } = await api.get('/settings/footer');
      this.left = data.left;
      this.right = data.right;
    },
    async update(left: string, right: string) {
      const { data } = await api.put('/settings/footer', { left, right });
      this.left = data.left;
      this.right = data.right;
    },
  },
});
