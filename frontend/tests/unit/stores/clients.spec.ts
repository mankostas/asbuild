/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

const useAuthStoreMock = vi.fn();
const useTenantStoreMock = vi.fn();

vi.mock('@/stores/auth', () => ({
  useAuthStore: useAuthStoreMock,
  can: vi.fn(),
}));

vi.mock('@/stores/tenant', () => ({
  useTenantStore: useTenantStoreMock,
}));

describe('clients store tenant filter behaviour', () => {
  let authState: { isSuperAdmin: boolean };

  beforeEach(() => {
    vi.resetModules();
    setActivePinia(createPinia());
    authState = { isSuperAdmin: false };
    useAuthStoreMock.mockReset();
    useTenantStoreMock.mockReset();
    useAuthStoreMock.mockImplementation(() => authState);
    useTenantStoreMock.mockReturnValue({
      currentTenantId: null,
      tenants: [],
    });
  });

  it('ignores tenant filter overrides for non super admins', async () => {
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();
    store.filters.tenantId = 'keep-me';

    store.setTenantFilter('123');

    expect(store.filters.tenantId).toBeNull();
  });

  it('persists tenant filter overrides for super admins', async () => {
    authState.isSuperAdmin = true;
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();

    store.setTenantFilter('456');

    expect(store.filters.tenantId).toBe('456');
  });
});
