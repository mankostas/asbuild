/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { fakeClientId, fakeTenantId } from '../../utils/publicIds';

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

    store.setTenantFilter(fakeTenantId('clients-non-admin'));

    expect(store.filters.tenantId).toBeNull();
  });

  it('persists tenant filter overrides for super admins', async () => {
    authState.isSuperAdmin = true;
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();

    const overrideTenantId = fakeTenantId('clients-admin');
    store.setTenantFilter(overrideTenantId);

    expect(store.filters.tenantId).toBe(overrideTenantId);
  });
});

describe('clients store id normalization', () => {
  it('normalizes ids on create responses', async () => {
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();
    const createdClientId = fakeClientId('clients-create');
    const createdTenantId = fakeTenantId('clients-create');
    clientsApiMock.create.mockResolvedValue({
      data: { id: createdClientId, tenant_id: createdTenantId, name: 'Client', status: 'active' },
    });

    const created = await store.create({ name: 'Client' });

    expect(clientsApiMock.create).toHaveBeenCalledWith({ name: 'Client' });
    expect(created.id).toBe(createdClientId);
    expect(created.tenant_id).toBe(createdTenantId);
    expect(store.clients[0].id).toBe(createdClientId);
  });

  it('normalizes ids on update and merges into state', async () => {
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();
    const existingClientId = fakeClientId('clients-existing');
    const updatedTenantId = fakeTenantId('clients-updated');
    store.clients = [{ id: existingClientId, name: 'Existing', tenant_id: fakeTenantId('clients-tenant') } as any];
    clientsApiMock.update.mockResolvedValue({
      data: { id: existingClientId, tenant_id: updatedTenantId, name: 'Updated', status: 'active' },
    });

    const updated = await store.update(existingClientId, { name: 'Updated' });

    expect(clientsApiMock.update).toHaveBeenCalledWith(existingClientId, { name: 'Updated' });
    expect(updated.id).toBe(existingClientId);
    expect(updated.tenant_id).toBe(updatedTenantId);
    expect(store.clients[0].id).toBe(existingClientId);
    expect(store.clients[0].name).toBe('Updated');
  });

  it('normalizes ids returned from bulk archive', async () => {
    const { useClientsStore } = await import('@/stores/clients');
    const store = useClientsStore();
    const archivedClientId = fakeClientId('clients-archived');
    const additionalClientId = fakeClientId('clients-additional');
    clientsApiMock.bulkArchive.mockResolvedValue({
      data: { data: [{ id: archivedClientId, name: 'Archived', status: 'inactive' }] },
    });

    const archived = await store.archiveMany([archivedClientId, additionalClientId]);

    expect(clientsApiMock.bulkArchive).toHaveBeenCalledWith([archivedClientId, additionalClientId]);
    expect(archived[0].id).toBe(archivedClientId);
    expect(store.clients.some((client) => client.id === archivedClientId)).toBe(true);
  });
});
