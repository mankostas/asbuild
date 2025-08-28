import { defineStore } from 'pinia';
import api from '@/services/api';

export const useCalendarStore = defineStore('calendar', {
  state: () => ({
    events: [] as any[],
    filters: {
      team_id: '',
      employee_id: '',
      type_id: '',
      status_id: '',
    } as Record<string, string>,
  }),
  actions: {
    async fetch(start: string, end: string) {
      const params: any = { start, end, ...this.filters };
      Object.keys(params).forEach((k) => {
        if (!params[k]) delete params[k];
      });
      const { data } = await api.get('/calendar/events', { params });
      this.events = data;
    },
  },
});
