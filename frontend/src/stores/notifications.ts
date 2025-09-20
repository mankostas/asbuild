import { defineStore } from 'pinia';
import api from '@/services/api';
import type { components } from '@/types/api';
import { withListParams, type ListParams } from './list';

type Notification = components['schemas']['Notification'];

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [] as Notification[],
    unreadCount: 0,
  }),
  actions: {
    async fetch(params: ListParams = {}) {
      const { data } = await api.get('/notifications', {
        params: withListParams(params),
      });
      const sorted = (data.data as Notification[]).sort(
        (a: Notification, b: Notification) =>
          new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
      );
      this.notifications = sorted;
      this.unreadCount = sorted.filter((n) => !n.read_at).length;
      return data.meta;
    },
    async fetchUnreadCount() {
      const { data } = await api.get('/notifications', {
        params: withListParams({ per_page: 100 }),
      });
      this.unreadCount = (data.data as Notification[]).filter(
        (n: Notification) => !n.read_at,
      ).length;
    },
    async markRead(id: string | number) {
      const identifier = String(id);
      await api.post(`/notifications/${identifier}/read`);
      const notif = this.notifications.find((n) => String(n.id) === identifier);
      if (notif) {
        notif.read_at = new Date().toISOString();
      }
      if (this.unreadCount > 0) {
        this.unreadCount -= 1;
      }
    },
  },
});
