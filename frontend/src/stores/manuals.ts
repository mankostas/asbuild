import { defineStore } from 'pinia';
import { openDB } from 'idb';
import api from '@/services/api';
import { withListParams, type ListParams } from './list';

const dbPromise = openDB('manuals', 1, {
  upgrade(db) {
    db.createObjectStore('pdfs');
  },
});

export const useManualsStore = defineStore('manuals', {
  state: () => ({
    manuals: [] as any[],
    favorites: JSON.parse(
      localStorage.getItem('manualFavorites') || '[]',
    ) as string[],
    recents: JSON.parse(
      localStorage.getItem('manualRecents') || '[]',
    ) as string[],
    offline: [] as string[],
  }),
  actions: {
    async fetch(params: ListParams = {}) {
      try {
        const { data } = await api.get('/manuals', {
          params: withListParams(params),
        });
        this.manuals = data.data;
        return data.meta;
      } catch (e) {
        this.manuals = [];
      }
    },
    async get(id: string | number) {
      if (!this.manuals.length) await this.fetch();
      const identifier = String(id);
      return this.manuals.find((m: any) => String(m.id) === identifier);
    },
    async downloadPdf(id: string) {
      const { data } = await api.get(`/manuals/${id}/download`, {
        responseType: 'blob',
      });
      return data;
    },
    async keepOffline(manual: any) {
      const blob = await this.downloadPdf(manual.id);
      const db = await dbPromise;
      await db.put('pdfs', { blob, updated_at: manual.updated_at }, manual.id);
      if (!this.offline.includes(manual.id)) this.offline.push(manual.id);
    },
    async removeOffline(id: string) {
      const db = await dbPromise;
      await db.delete('pdfs', id);
      this.offline = this.offline.filter((i) => i !== id);
    },
    async isOffline(id: string) {
      if (this.offline.includes(id)) return true;
      const db = await dbPromise;
      const exists = await db.get('pdfs', id);
      if (exists) {
        if (!this.offline.includes(id)) this.offline.push(id);
        return true;
      }
      return false;
    },
    async loadOffline(id: string) {
      const db = await dbPromise;
      return db.get('pdfs', id);
    },
    async loadOfflineList() {
      const db = await dbPromise;
      this.offline = (await db.getAllKeys('pdfs')) as string[];
    },
    toggleFavorite(id: string) {
      if (this.favorites.includes(id)) {
        this.favorites = this.favorites.filter((f) => f !== id);
      } else {
        this.favorites.push(id);
      }
      localStorage.setItem('manualFavorites', JSON.stringify(this.favorites));
    },
    addRecent(id: string) {
      this.recents = [id, ...this.recents.filter((r) => r !== id)].slice(0, 10);
      localStorage.setItem('manualRecents', JSON.stringify(this.recents));
    },
  },
});
