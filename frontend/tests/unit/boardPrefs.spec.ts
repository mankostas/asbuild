import { describe, it, expect, beforeEach } from 'vitest';
import { loadBoardPrefs, saveBoardPrefs, BoardPrefs } from '@/services/boardPrefs';

describe('boardPrefs', () => {
  beforeEach(() => {
    (global as any).localStorage = {
      store: {} as Record<string, string>,
      getItem(key: string) {
        return this.store[key] || null;
      },
      setItem(key: string, value: string) {
        this.store[key] = value;
      },
      removeItem(key: string) {
        delete this.store[key];
      },
      clear() {
        this.store = {};
      },
    };
  });

  it('persists preferences roundtrip', () => {
    const prefs: BoardPrefs = {
      filters: {
        statusIds: ['open'],
        typeId: '1',
        assigneeId: '42',
        priority: 'high',
        hasPhotos: true,
        dates: { from: '2024-01-01', to: '2024-02-01' },
      },
      sorting: { key: 'due_at', dir: 'desc' },
      cardDensity: 'compact',
    };
    saveBoardPrefs(1, prefs);
    const loaded = loadBoardPrefs(1);
    expect(loaded).toEqual(prefs);
  });
});
