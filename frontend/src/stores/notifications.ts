import { defineStore } from 'pinia';
import api from '@/services/api';

interface Notification {
  id: number;
  message: string;
  link?: string | null;
  created_at: string;
  read_at?: string | null;
}

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [] as Notification[],
    unreadCount: 0,
  }),
  actions: {
    async fetchAll() {
      const { data } = await api.get('/notifications');
      const sorted = data.sort(
        (a: Notification, b: Notification) =>
          new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
      );
      this.notifications = sorted;
      this.unreadCount = sorted.filter((n) => !n.read_at).length;
    },
    async fetchUnreadCount() {
      const { data } = await api.get('/notifications');
      this.unreadCount = data.filter((n: any) => !n.read_at).length;
    },
    async markRead(id: number) {
      await api.post(`/notifications/${id}/read`);
      const notif = this.notifications.find((n) => n.id === id);
      if (notif) {
        notif.read_at = new Date().toISOString();
      }
      if (this.unreadCount > 0) {
        this.unreadCount -= 1;
      }
    },
  },
});
