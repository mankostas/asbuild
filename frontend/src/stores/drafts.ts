import { defineStore } from 'pinia';
import { openDB } from 'idb';
import { uploadFile } from '@/services/uploader';

const dbPromise = openDB('drafts', 1, {
  upgrade(db) {
    db.createObjectStore('appointments');
  },
});

export const useDraftsStore = defineStore('drafts', {
  state: () => ({
    queue: [] as { id: string; data: any }[],
  }),
  actions: {
    async save(id: string, data: any) {
      const db = await dbPromise;
      await db.put('appointments', data, id);
      if (!this.queue.find((q) => q.id === id)) {
        this.queue.push({ id, data });
      } else {
        const idx = this.queue.findIndex((q) => q.id === id);
        this.queue[idx].data = data;
      }
    },
    async load(id: string) {
      const db = await dbPromise;
      return db.get('appointments', id);
    },
    async remove(id: string) {
      const db = await dbPromise;
      await db.delete('appointments', id);
      this.queue = this.queue.filter((q) => q.id !== id);
    },
    async retry(id: string) {
      const item = this.queue.find((q) => q.id === id);
      if (!item) return;
      try {
        for (const photo of item.data.photos || []) {
          await uploadFile(photo);
        }
        await this.remove(id);
      } catch (e) {
        // leave in queue
      }
    },
  },
});
