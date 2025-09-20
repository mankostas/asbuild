/** @vitest-environment jsdom */
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { fakeClientId, fakePublicId, fakeTaskId, fakeUserId } from '../../utils/publicIds';

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
    const taskId = fakeTaskId('normalize');
    const taskPublicId = fakePublicId('task-normalized');
    const assigneeId = fakeUserId('assignee');
    const assigneeFallback = fakeUserId('assignee-fallback');
    const clientId = fakeClientId('client');
    const clientFallback = fakeClientId('client-fallback');
    const normalized = store.normalize({
      id: taskId,
      public_id: taskPublicId,
      assignee: { id: assigneeFallback, public_id: assigneeId, name: 'Assignee' },
      client: { id: clientFallback, public_id: clientId, name: 'Client' },
    });

    expect(normalized.id).toBe(taskId);
    expect(normalized.public_id).toBe(taskPublicId);
    expect(normalized.assignee).toEqual({ id: assigneeId, name: 'Assignee' });
    expect(normalized.client).toEqual({ id: clientId, name: 'Client' });
  });

  it('builds payloads with string ids', async () => {
    const { useTasksStore } = await import('@/stores/tasks');
    const store = useTasksStore();

    const hashedAssignee = fakeUserId('payload');
    const hashed = store.toPayload({ assignee: { id: hashedAssignee } });
    const numeric = store.toPayload({ assignee: { id: 15 } as any });

    expect(hashed.assignee?.id).toBe(hashedAssignee);
    expect(numeric.assignee?.id).toBe('15');
  });

  it('creates tasks with normalized ids', async () => {
    const { useTasksStore } = await import('@/stores/tasks');
    const store = useTasksStore();
    const createdTaskId = fakeTaskId('created');
    const createdAssigneeId = fakeUserId('created-assignee');
    const createdClientId = fakeClientId('created-client');
    postMock.mockResolvedValue({
      data: {
        id: createdTaskId,
        assignee: { id: createdAssigneeId, name: 'Assigned' },
        client: { id: createdClientId, name: 'Client' },
      },
    });

    const payload = { title: 'Test', assignee: { id: createdAssigneeId } };
    const created = await store.create(payload);

    expect(postMock).toHaveBeenCalledWith('/tasks', {
      title: 'Test',
      assignee: { id: createdAssigneeId },
    });
    expect(created.id).toBe(createdTaskId);
    expect(created.assignee?.id).toBe(createdAssigneeId);
    expect(store.tasks[0].client?.id).toBe(createdClientId);
  });

  it('updates tasks while preserving string ids', async () => {
    const { useTasksStore } = await import('@/stores/tasks');
    const store = useTasksStore();
    const existingTaskId = fakeTaskId('existing');
    const existingAssigneeId = fakeUserId('existing-assignee');
    const updatedAssigneeId = fakeUserId('updated-assignee');
    const updatedClientId = fakeClientId('updated-client');
    store.tasks = [
      store.normalize({
        id: existingTaskId,
        assignee: { id: existingAssigneeId, name: 'Old' },
        client: { id: updatedClientId, name: 'Client' },
      }),
    ];
    patchMock.mockResolvedValue({
      data: {
        id: existingTaskId,
        assignee: { id: updatedAssigneeId, name: 'Next' },
        client: { id: updatedClientId, name: 'Client' },
      },
    });

    const updated = await store.update(existingTaskId, {
      title: 'Updated',
      assignee: { id: updatedAssigneeId },
    });

    expect(patchMock).toHaveBeenCalledWith(`/tasks/${existingTaskId}`, {
      title: 'Updated',
      assignee: { id: updatedAssigneeId },
    });
    expect(updated.id).toBe(existingTaskId);
    expect(updated.assignee?.id).toBe(updatedAssigneeId);
    expect(store.tasks[0].assignee?.id).toBe(updatedAssigneeId);
    expect(store.tasks[0].client?.id).toBe(updatedClientId);
  });
});
