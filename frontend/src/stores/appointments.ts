import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAppointmentsStore = defineStore('appointments', {
  state: () => ({
    appointments: [] as any[],
  }),
  actions: {
    async fetch() {
      try {
        const { data } = await api.get('/appointments');
        this.appointments = data;
      } catch (e) {
        this.appointments = [];
      }
    },
    async get(id: string) {
      if (!this.appointments.length) await this.fetch();
      return this.appointments.find((a: any) => a.id == id);
    },
  },
});
