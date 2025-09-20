/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

const useAuthStoreMock = vi.fn();
const useTenantStoreMock = vi.fn();
const clientsApiMock = {
  list: vi.fn(),
  get: vi.fn(),
  create: vi.fn(),
  update: vi.fn(),
  remove: vi.fn(),
  restore: vi.fn(),
  archive: vi.fn(),
  unarchive: vi.fn(),
  bulkArchive: vi.fn(),
  bulkDelete: vi.fn(),
  toggleStatus: vi.fn(),
};

vi.mock('@/stores/auth', () => ({
  useAuthStore: useAuthStoreMock,
  can: vi.fn(),
}));

vi.mock('@/stores/tenant', () => ({
  useTenantStore: useTenantStoreMock,
}));

vi.mock('@/services/api/clients', () => ({
  __esModule: true,
  default: clientsApiMock,
}));

let authState: { isSuperAdmin: boolean };

beforeEach(() => {
  vi.resetModules();
  setActivePinia(createPinia());
  authState = { isSuperAdmin: false };
  useAuthStoreMock.mockReset();
  useTenantStoreMock.mockReset();
  Object.values(clientsApiMock).forEach((mock) => mock.mockReset());
  useAuthStoreMock.mockImplementation(() => authState);
  useTenantStoreMock.mockReturnValue({
    currentTenantId: null,
    tenants: [],
  });
});

describe('clients store tenant filter behaviour', () => {
  beforeEach(() => {
    clientsApiMock.list.mockResolvedValue({ data: { data: [], meta: {} } });
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

describe('clients store id normalization', () => {
  it('normalizes ids on create responses', async () => {
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();
    clientsApiMock.create.mockResolvedValue({
      data: { id: 101, tenant_id: 202, name: 'Client', status: 'active' },
    });

    const created = await store.create({ name: 'Client' });

    expect(clientsApiMock.create).toHaveBeenCalledWith({ name: 'Client' });
    expect(created.id).toBe('101');
    expect(created.tenant_id).toBe('202');
    expect(store.clients[0].id).toBe('101');
  });

  it('normalizes ids on update and merges into state', async () => {
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();
    store.clients = [{ id: '300', name: 'Existing', tenant_id: 'tenant-1' } as any];
    clientsApiMock.update.mockResolvedValue({
      data: { id: 300, tenant_id: 404, name: 'Updated', status: 'active' },
    });

    const updated = await store.update('300', { name: 'Updated' });

    expect(clientsApiMock.update).toHaveBeenCalledWith('300', { name: 'Updated' });
    expect(updated.id).toBe('300');
    expect(updated.tenant_id).toBe('404');
    expect(store.clients[0].id).toBe('300');
    expect(store.clients[0].name).toBe('Updated');
  });

  it('normalizes ids returned from bulk archive', async () => {
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();
    clientsApiMock.bulkArchive.mockResolvedValue({
      data: { data: [{ id: 555, name: 'Archived', status: 'inactive' }] },
    });

    const archived = await store.archiveMany([555, '666']);

    expect(clientsApiMock.bulkArchive).toHaveBeenCalledWith(['555', '666']);
    expect(archived[0].id).toBe('555');
    expect(store.clients.some((client) => client.id === '555')).toBe(true);
  });
});
