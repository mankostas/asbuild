import { defineStore } from 'pinia';
import api from '@/services/api';

export const useFooterStore = defineStore('footer', {
  state: () => ({
    text: '' as string,
  }),
  actions: {
    async load() {
      const { data } = await api.get('/settings/footer');
      this.text = data.text;
    },
    async update(text: string) {
      const { data } = await api.put('/settings/footer', { text });
      this.text = data.text;
    },
  },
});
