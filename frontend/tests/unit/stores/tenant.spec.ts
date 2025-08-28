import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
  },
}));

import api from '@/services/api';
import { TENANT_ID_KEY } from '@/config/app';

describe('tenant store', () => {
  beforeEach(() => {
    const store: Record<string, string> = {};
    vi.stubGlobal('localStorage', {
      getItem: (key: string) => (key in store ? store[key] : null),
      setItem: (key: string, value: string) => {
        store[key] = String(value);
      },
      removeItem: (key: string) => {
        delete store[key];
      },
      clear: () => {
        Object.keys(store).forEach((k) => delete store[k]);
      },
    });
    vi.stubGlobal('window', { location: { reload: vi.fn() } });
    vi.resetModules();
    setActivePinia(createPinia());
  });

  it('clears stale tenant id when not returned from API', async () => {
    localStorage.setItem(TENANT_ID_KEY, '999');
    const { useTenantStore } = await import('@/stores/tenant');
    (api.get as any).mockResolvedValue({ data: { data: [{ id: 1 }], meta: {} } });
    const store = useTenantStore();
    await store.loadTenants();
    expect(localStorage.getItem(TENANT_ID_KEY)).toBeNull();
    expect((window.location.reload as any)).toHaveBeenCalled();
    expect(store.currentTenantId).toBe('');
  });
});
