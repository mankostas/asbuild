import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useRolesStore } from '@/stores/roles';
import { fakeRoleId, fakeTenantId, fakeUserId } from '../../utils/publicIds';

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
    const tenantScopeId = fakeTenantId('roles-scope');
    const scopedRoleId = fakeRoleId('roles-desc');
    (api.get as any).mockResolvedValue({ data: { data: [{ id: scopedRoleId, description: 'desc' }] } });
    const store = useRolesStore();
    await store.fetch({ scope: 'tenant', tenant_id: tenantScopeId });
    expect(api.get).toHaveBeenCalledWith('/roles', {
      params: {
        scope: 'tenant',
        tenant_id: tenantScopeId,
        page: 1,
        per_page: 20,
        search: '',
        sort: '',
        dir: 'asc',
      },
    });
    expect(store.roles).toEqual([{ id: scopedRoleId, description: 'desc' }]);
  });

  it('omits tenant_id when scope is all', async () => {
    (api.get as any).mockResolvedValue({ data: { data: [] } });
    const store = useRolesStore();
    await store.fetch({ scope: 'all', tenant_id: fakeTenantId('roles-all') });
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
    const assignmentRoleId = fakeRoleId('assign');
    const assignmentUserId = fakeUserId('assigned');
    const assignmentTenantId = fakeTenantId('assigned');
    await store.assignUser(assignmentRoleId, {
      user_id: assignmentUserId,
      tenant_id: assignmentTenantId,
    });
    expect(api.post).toHaveBeenCalledWith(`/roles/${assignmentRoleId}/assign`, {
      user_id: assignmentUserId,
      tenant_id: assignmentTenantId,
    });
  });

  it('creates role with description', async () => {
    const createdRoleId = fakeRoleId('created');
    (api.post as any).mockResolvedValue({ data: { id: createdRoleId } });
    const store = useRolesStore();
    await store.create({ name: 'Tester', description: 'desc' } as any);
    expect(api.post).toHaveBeenCalledWith('/roles', {
      name: 'Tester',
      description: 'desc',
    });
  });
});
