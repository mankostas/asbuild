import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
  },
}));

vi.mock('@/stores/lookups', () => ({
  useLookupsStore: vi.fn(() => ({ $reset: vi.fn() })),
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
    // A page reload is no longer triggered automatically when the tenant
    // changes; the store simply updates its state. Verify only the state
    // and storage are cleared.
    expect(store.currentTenantId).toBe('');
  });

  it('indicates when the tenant id changes', async () => {
    const { useTenantStore } = await import('@/stores/tenant');
    const store = useTenantStore();
    expect(store.setTenant('123')).toBe(true);
    expect(store.setTenant('123')).toBe(false);
  });

  it('handles 403 errors when loading tenants', async () => {
    const { useTenantStore } = await import('@/stores/tenant');
    (api.get as any).mockRejectedValue({ status: 403 });
    const store = useTenantStore();
    await expect(store.loadTenants()).resolves.toEqual({ total: 0 });
    expect(store.tenants).toEqual([]);
  });
});
