/** @vitest-environment jsdom */
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

const postMock = vi.fn();
const patchMock = vi.fn();
const getMock = vi.fn();

vi.mock('@/services/api', () => ({
  __esModule: true,
  default: {
    get: getMock,
    post: postMock,
    patch: patchMock,
  },
}));

describe('tasks store id normalization', () => {
  beforeEach(() => {
    vi.resetModules();
    setActivePinia(createPinia());
    postMock.mockReset();
    patchMock.mockReset();
    getMock.mockReset();
  });

  it('normalizes ids and nested relationships to strings', async () => {
    const { useTasksStore } = await import('@/stores/tasks');
    const store = useTasksStore();
    const normalized = store.normalize({
      id: 42,
      public_id: 'tsk_hash',
      assignee: { id: 9, public_id: 'user_hash', name: 'Assignee' },
      client: { id: 7, public_id: 'client_hash', name: 'Client' },
    });

    expect(normalized.id).toBe('42');
    expect(normalized.public_id).toBe('tsk_hash');
    expect(normalized.assignee).toEqual({ id: 'user_hash', name: 'Assignee' });
    expect(normalized.client).toEqual({ id: 'client_hash', name: 'Client' });
  });

  it('builds payloads with string ids', async () => {
    const { useTasksStore } = await import('@/stores/tasks');
    const store = useTasksStore();

    const hashed = store.toPayload({ assignee: { id: 'user_hash' } });
    const numeric = store.toPayload({ assignee: { id: 15 } as any });

    expect(hashed.assignee?.id).toBe('user_hash');
    expect(numeric.assignee?.id).toBe('15');
  });

  it('creates tasks with normalized ids', async () => {
    const { useTasksStore } = await import('@/stores/tasks');
    const store = useTasksStore();
    postMock.mockResolvedValue({
      data: {
        id: 1001,
        assignee: { id: 22, name: 'Assigned' },
        client: { id: 'client_77', name: 'Client' },
      },
    });

    const payload = { title: 'Test', assignee: { id: 'user_hash' } };
    const created = await store.create(payload);

    expect(postMock).toHaveBeenCalledWith('/tasks', {
      title: 'Test',
      assignee: { id: 'user_hash' },
    });
    expect(created.id).toBe('1001');
    expect(created.assignee?.id).toBe('22');
    expect(store.tasks[0].client?.id).toBe('client_77');
  });

  it('updates tasks while preserving string ids', async () => {
    const { useTasksStore } = await import('@/stores/tasks');
    const store = useTasksStore();
    store.tasks = [
      store.normalize({
        id: 2002,
        assignee: { id: 11, name: 'Old' },
        client: { id: 33, name: 'Client' },
      }),
    ];
    patchMock.mockResolvedValue({
      data: {
        id: 2002,
        assignee: { id: 'user_next', name: 'Next' },
        client: { id: 77, name: 'Client' },
      },
    });

    const updated = await store.update(2002, { title: 'Updated', assignee: { id: 'user_next' } });

    expect(patchMock).toHaveBeenCalledWith('/tasks/2002', {
      title: 'Updated',
      assignee: { id: 'user_next' },
    });
    expect(updated.id).toBe('2002');
    expect(updated.assignee?.id).toBe('user_next');
    expect(store.tasks[0].assignee?.id).toBe('user_next');
    expect(store.tasks[0].client?.id).toBe('77');
  });
});
