import { describe, it, beforeEach, expect, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import MockAdapter from 'axios-mock-adapter';
import { TENANT_HEADER } from '@/config/app';

describe('task type versions store', () => {
  let api: any;
  let mock: MockAdapter;
  let useTenantStore: any;
  let useTaskTypeVersionsStore: any;

  beforeEach(async () => {
    vi.resetModules();
    const storage: Record<string, string> = {};
    vi.stubGlobal('localStorage', {
      getItem: (k: string) => storage[k] ?? null,
      setItem: (k: string, v: string) => {
        storage[k] = String(v);
      },
      removeItem: (k: string) => {
        delete storage[k];
      },
      clear: () => {
        Object.keys(storage).forEach((k) => delete storage[k]);
      },
    });
    setActivePinia(createPinia());
    ({ default: api } = await import('@/services/api'));
    ({ useTenantStore } = await import('@/stores/tenant'));
    ({ useTaskTypeVersionsStore } = await import('@/stores/taskTypeVersions'));
    mock = new MockAdapter(api);
  });

  it('sends tenant header when listing and publishing', async () => {
    const tenant = useTenantStore();
    tenant.currentTenantId = 99;
    const store = useTaskTypeVersionsStore();

    mock.onGet('/sanctum/csrf-cookie').reply(204);
    mock.onGet('/task-type-versions').reply((config) => {
      expect(config.headers?.[TENANT_HEADER]).toBe('99');
      return [200, { data: [] }];
    });

    mock.onPost('/task-type-versions/1/publish').reply((config) => {
      expect(config.headers?.[TENANT_HEADER]).toBe('99');
      return [200, { data: {} }];
    });

    await store.list(1);
    await store.publish(1);
  });
});
