import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useRolesStore } from '@/stores/roles';

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}));

import api from '@/services/api';

describe('roles store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('fetches roles with params', async () => {
    (api.get as any).mockResolvedValue({ data: { data: [{ id: 1, description: 'desc' }] } });
    const store = useRolesStore();
    await store.fetch({ scope: 'tenant', tenant_id: '1' });
    expect(api.get).toHaveBeenCalledWith('/roles', {
      params: {
        scope: 'tenant',
        tenant_id: '1',
        page: 1,
        per_page: 20,
        search: '',
        sort: '',
        dir: 'asc',
      },
    });
    expect(store.roles).toEqual([{ id: 1, description: 'desc' }]);
  });

  it('omits tenant_id when scope is all', async () => {
    (api.get as any).mockResolvedValue({ data: { data: [] } });
    const store = useRolesStore();
    await store.fetch({ scope: 'all', tenant_id: '1' });
    expect(api.get).toHaveBeenCalledWith('/roles', {
      params: {
        scope: 'all',
        page: 1,
        per_page: 20,
        search: '',
        sort: '',
        dir: 'asc',
      },
    });
  });

  it('assigns role to user', async () => {
    (api.post as any).mockResolvedValue({});
    const store = useRolesStore();
    await store.assignUser(2, { user_id: 3, tenant_id: '4' });
    expect(api.post).toHaveBeenCalledWith('/roles/2/assign', {
      user_id: 3,
      tenant_id: '4',
    });
  });

  it('creates role with description', async () => {
    (api.post as any).mockResolvedValue({ data: { id: 1 } });
    const store = useRolesStore();
    await store.create({ name: 'Tester', description: 'desc' } as any);
    expect(api.post).toHaveBeenCalledWith('/roles', {
      name: 'Tester',
      description: 'desc',
    });
  });
});
