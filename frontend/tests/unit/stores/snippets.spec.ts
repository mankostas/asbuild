import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

// The snippets store performs asynchronous operations when creating and
// retrieving snippets. These tests ensure we wait for those operations to
// complete before making assertions.

describe('snippets store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('creates and retrieves a snippet', async () => {
    const { useSnippetsStore } = await import('@/stores/snippets');
    const store = useSnippetsStore();
    const created = await store.create({ name: 'Group', fields: ['a'] });
    const loaded = await store.get(created.id);
    expect(loaded).toEqual(created);
  });
});
