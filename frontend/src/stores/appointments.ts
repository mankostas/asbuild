// @ts-nocheck
import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAppointmentsStore = defineStore('appointments', {
  state: () => ({
    appointments: [],
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
    async get(id) {
      if (!this.appointments.length) await this.fetch();
      return this.appointments.find((a) => a.id == id);
    },
  },
});
