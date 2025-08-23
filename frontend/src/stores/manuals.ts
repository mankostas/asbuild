// @ts-nocheck
import { defineStore } from 'pinia';
import { openDB } from 'idb';
import api from '@/services/api';

const dbPromise = openDB('manuals', 1, {
  upgrade(db) {
    db.createObjectStore('pdfs');
  },
});

export const useManualsStore = defineStore('manuals', {
  state: () => ({
    manuals: [],
    favorites: JSON.parse(localStorage.getItem('manualFavorites') || '[]'),
    recents: JSON.parse(localStorage.getItem('manualRecents') || '[]'),
    offline: [],
  }),
  actions: {
    async fetch(q = '') {
      try {
        const { data } = await api.get('/manuals', q ? { params: { q } } : undefined);
        this.manuals = data;
      } catch (e) {
        this.manuals = [];
      }
    },
    async get(id) {
      if (!this.manuals.length) await this.fetch();
      return this.manuals.find((m) => m.id == id);
    },
    async downloadPdf(id) {
      const { data } = await api.get(`/manuals/${id}/download`, { responseType: 'blob' });
      return data;
    },
    async keepOffline(manual) {
      const blob = await this.downloadPdf(manual.id);
      const db = await dbPromise;
      await db.put('pdfs', { blob, updated_at: manual.updated_at }, manual.id);
      if (!this.offline.includes(manual.id)) this.offline.push(manual.id);
    },
    async removeOffline(id) {
      const db = await dbPromise;
      await db.delete('pdfs', id);
      this.offline = this.offline.filter((i) => i !== id);
    },
    async isOffline(id) {
      if (this.offline.includes(id)) return true;
      const db = await dbPromise;
      const exists = await db.get('pdfs', id);
      if (exists) {
        if (!this.offline.includes(id)) this.offline.push(id);
        return true;
      }
      return false;
    },
    async loadOffline(id) {
      const db = await dbPromise;
      return db.get('pdfs', id);
    },
    async loadOfflineList() {
      const db = await dbPromise;
      this.offline = await db.getAllKeys('pdfs');
    },
    toggleFavorite(id) {
      if (this.favorites.includes(id)) {
        this.favorites = this.favorites.filter((f) => f !== id);
      } else {
        this.favorites.push(id);
      }
      localStorage.setItem('manualFavorites', JSON.stringify(this.favorites));
    },
    addRecent(id) {
      this.recents = [id, ...this.recents.filter((r) => r !== id)].slice(0, 10);
      localStorage.setItem('manualRecents', JSON.stringify(this.recents));
    },
  },
});
