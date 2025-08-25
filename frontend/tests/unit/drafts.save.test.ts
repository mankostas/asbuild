import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('idb', () => {
  const store: Record<string, any> = {};
  return {
    openDB: vi.fn(async () => ({
      put: async (_s: string, data: any, id: string) => {
        store[id] = data;
      },
      get: async (_s: string, id: string) => store[id],
      delete: async (_s: string, id: string) => {
        delete store[id];
      },
    })),
  };
});

vi.mock('@/services/uploader', () => ({
  uploadFile: vi.fn(async () => {}),
}));

describe('drafts store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('saves draft and adds to queue', async () => {
    const { useDraftsStore } = await import('@/stores/drafts');
    const store = useDraftsStore();
    await store.save('1', { kau: 'abc' });
    const loaded = await store.load('1');
    expect(loaded).toEqual({ kau: 'abc' });
    expect(store.queue).toHaveLength(1);
    expect(store.queue[0].id).toBe('1');
  });
});
