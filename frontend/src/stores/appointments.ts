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
    async create(payload: any) {
      const data = { ...payload };
      if (payload.assignee) {
        data.assignee = {
          kind: payload.assignee.kind,
          id: payload.assignee.id,
        };
      }
      const res = await api.post('/appointments', data);
      this.appointments.push(res.data);
      return res.data;
    },
    async update(id: string, payload: any) {
      const data = { ...payload };
      if (payload.assignee) {
        data.assignee = {
          kind: payload.assignee.kind,
          id: payload.assignee.id,
        };
      }
      const { data: updated } = await api.patch(`/appointments/${id}`, data);
      this.appointments = this.appointments.map((a: any) => (a.id === id ? updated : a));
      return updated;
    },
  },
});
