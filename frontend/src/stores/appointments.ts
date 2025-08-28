import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAppointmentsStore = defineStore('appointments', {
  state: () => ({
    appointments: [] as any[],
  }),
  actions: {
    normalize(payload: any) {
      if (payload.assignee) {
        payload.assignee = {
          kind: payload.assignee.kind,
          id: payload.assignee.id,
          label: payload.assignee.label,
        };
      }
      return payload;
    },
    toPayload(payload: any) {
      const data = { ...payload };
      if (payload.assignee) {
        data.assignee = {
          kind: payload.assignee.kind,
          id: payload.assignee.id,
        };
      }
      return data;
    },
    async fetch() {
      try {
        const { data } = await api.get('/appointments');
        this.appointments = data.map((a: any) => this.normalize(a));
      } catch (e) {
        this.appointments = [];
      }
    },
    async get(id: string) {
      if (!this.appointments.length) await this.fetch();
      return this.appointments.find((a: any) => a.id == id);
    },
    async create(payload: any) {
      const res = await api.post('/appointments', this.toPayload(payload));
      const appt = this.normalize(res.data);
      this.appointments.push(appt);
      return appt;
    },
    async update(id: string, payload: any) {
      const { data: updated } = await api.patch(`/appointments/${id}`, this.toPayload(payload));
      const appt = this.normalize(updated);
      this.appointments = this.appointments.map((a: any) => (a.id === id ? appt : a));
      return appt;
    },
  },
});
