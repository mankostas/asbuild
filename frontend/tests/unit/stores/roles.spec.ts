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
    (api.get as any).mockResolvedValue({ data: [{ id: 1 }] });
    const store = useRolesStore();
    await store.fetch({ scope: 'tenant', tenantId: '1' });
    expect(api.get).toHaveBeenCalledWith('/roles', {
      params: { scope: 'tenant', tenant_id: '1' },
    });
    expect(store.roles).toEqual([{ id: 1 }]);
  });

  it('assigns role to user', async () => {
    (api.post as any).mockResolvedValue({});
    const store = useRolesStore();
    await store.assignUser({ roleId: 2, userId: 3, tenantId: '4' });
    expect(api.post).toHaveBeenCalledWith('/roles/2/assign', {
      user_id: 3,
      tenant_id: '4',
    });
  });
});
