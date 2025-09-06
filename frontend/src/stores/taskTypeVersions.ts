import { defineStore } from 'pinia';
import api from '@/services/api';
import { unpublishTaskTypeVersion } from '@/services/types';

export const useTaskTypeVersionsStore = defineStore('taskTypeVersions', {
  actions: {
    async list(taskTypeId: number) {
      const { data } = await api.get('/task-type-versions', { params: { task_type_id: taskTypeId } });
      return data.data;
    },
    async create(taskTypeId: number) {
      const { data } = await api.post(`/task-types/${taskTypeId}/versions`);
      return data.data;
    },
    async publish(id: number) {
      const { data } = await api.post(`/task-type-versions/${id}/publish`);
      return data.data;
    },
    async deprecate(id: number) {
      const { data } = await api.post(`/task-type-versions/${id}/deprecate`);
      return data.data;
    },
    async unpublish(id: number) {
      const data = await unpublishTaskTypeVersion(id);
      return data.data;
    },
  },
});
