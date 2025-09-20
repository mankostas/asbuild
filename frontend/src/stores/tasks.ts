import { defineStore } from 'pinia';
import api from '@/services/api';
import { withListParams, type ListParams } from './list';

export const useTasksStore = defineStore('tasks', {
  state: () => ({
    tasks: [] as any[],
  }),
  actions: {
    normalize(payload: any) {
      if (payload.assignee) {
        payload.assignee = {
          id: payload.assignee.id,
          name: payload.assignee.name,
        };
      }
      if (payload.client) {
        payload.client = {
          id: payload.client.id,
          name: payload.client.name,
        };
      }
      return payload;
    },
    toPayload(payload: any) {
      const data = { ...payload };
      if (payload.assignee) {
        data.assignee = {
          id: payload.assignee.id,
        };
      }
      return data;
    },
    async fetch(params: ListParams = {}) {
      try {
        const query = withListParams({ ...params, include: 'client' });
        const { data } = await api.get('/tasks', {
          params: query,
        });
        this.tasks = data.data.map((a: any) => this.normalize(a));
        return data.meta;
      } catch (e) {
        this.tasks = [];
      }
    },
    async get(id: string | number) {
      if (!this.tasks.length) await this.fetch();
      const identifier = String(id);
      return this.tasks.find((a: any) => String(a.id) === identifier);
    },
    async create(payload: any) {
      const res = await api.post('/tasks', this.toPayload(payload));
      const task = this.normalize(res.data);
      this.tasks.push(task);
      return task;
    },
    async update(id: string | number, payload: any) {
      const identifier = String(id);
      const { data: updated } = await api.patch(`/tasks/${identifier}`, this.toPayload(payload));
      const task = this.normalize(updated);
      this.tasks = this.tasks.map((a: any) => (String(a.id) === identifier ? task : a));
      return task;
    },
  },
});
