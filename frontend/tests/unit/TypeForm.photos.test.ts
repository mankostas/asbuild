/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { ref } from 'vue';
import { fakePublicId, fakeRoleId, fakeTenantId } from '../utils/publicIds';

// Mock router and i18n dependencies
vi.mock('vue-router', () => ({
  useRoute: () => ({ name: 'taskTypes.create', params: {}, query: {} }),
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (k: string) => k, locale: ref('en') }),
}));

// Mock stores and utilities
vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({ isSuperAdmin: true, user: {} }),
  can: () => true,
}));

vi.mock('@/stores/tenant', () => ({
  useTenantStore: () => ({
    tenants: [],
    tenantAllowedAbilities: () => [],
    loadTenants: vi.fn(),
    setTenant: vi.fn(),
  }),
}));

vi.mock('@/stores/features', () => ({
  useFeaturesStore: () => ({
    load: vi.fn().mockResolvedValue({}),
    abilitiesFor: () => [],
  }),
}));

vi.mock('@/utils/ability', () => ({
  default: () => true,
}));

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
  },
}));

// Mock other imports used solely for component rendering
vi.mock('@/styles/types-builder.css', () => ({}), { virtual: true });
vi.mock('vuedraggable', () => ({ default: {} }));
vi.mock('@/components/types/CanvasSection.vue', () => ({ default: {} }));
vi.mock('@/components/types/Inspector/InspectorTabs.vue', () => ({ default: {} }));
vi.mock('@/components/forms/JsonSchemaForm.vue', () => ({ default: {} }));
vi.mock('@/components/types/StatusesEditor', () => ({ default: {} }));
vi.mock('@/components/types/TransitionsEditor.vue', () => ({ default: {} }));
vi.mock('@/components/types/SLAPolicyEditor.vue', () => ({ default: {} }));
vi.mock('@/components/types/AutomationsEditor.vue', () => ({ default: {} }));
vi.mock('@/components/types/PermissionsMatrix.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Breadcrumbs/index.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Button/index.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Select/index.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Card/index.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Tabs/index.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Drawer/index.vue', () => ({ default: {} }));
vi.mock('@/components/types/FieldPalette.vue', () => ({ default: {} }));
vi.mock('@/components/types/TypeMetaBar.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Dropdown/index.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Icon/index.vue', () => ({ default: {} }));
vi.mock('@/components/ui/Skeleton.vue', () => ({ default: {} }));
vi.mock('@headlessui/vue', () => ({
  Tab: { template: '<div><slot /></div>' },
  TabPanel: { template: '<div><slot /></div>' },
  MenuItem: { template: '<div><slot /></div>' },
}));

vi.mock('sweetalert2', () => ({
  default: { fire: vi.fn() },
}));

import api from '@/services/api';
import TypeForm from '@/views/types/TypeForm.vue';

const sectionId = fakePublicId('type-form-section');
const tenantContextId = fakeTenantId('type-form-tenant');
const tenantRoleId = fakeRoleId('tenant-role');
const globalRoleId = fakeRoleId('global-role');

describe('TypeForm photo serialization', () => {
  it('includes empty photos array when none are defined', () => {
    const result = (TypeForm as any).setup({}, { expose: () => {}, emit: () => {} });
    const sections = result.sections as any;
    const previewSchema = result.previewSchema as any;

    sections.value = [
      {
        id: sectionId,
        key: 'main',
        label: { en: 'Main', el: 'Main' },
        fields: [],
        photos: [],
        cols: 2,
        tabs: [],
      },
    ];

    expect(previewSchema.value.sections[0].photos).toEqual([]);
  });
});

describe('TypeForm tenant workflow', () => {
  it('loads tenant statuses and leaves transitions empty', async () => {
    const result = (TypeForm as any).setup({}, { expose: () => {}, emit: () => {} });
    const mockGet = api.get as unknown as vi.Mock;
    mockGet.mockImplementation((url: string, opts: any) => {
      if (url === '/task-statuses' && opts?.params?.tenant_id === tenantContextId) {
        return Promise.resolve({ data: { data: [{ slug: 'open' }, { slug: 'closed' }] } });
      }
      return Promise.resolve({ data: {} });
    });

    await result.refreshTenant(tenantContextId, '');
    expect(result.statuses.value).toEqual(['open', 'closed']);
    expect(result.statusFlow.value).toEqual([]);

    result.statusFlow.value.push(['open', 'closed']);
    expect(result.statusFlow.value.length).toBe(1);
  });

  it('preserves global roles when fetching tenant roles', async () => {
    const result = (TypeForm as any).setup({}, { expose: () => {}, emit: () => {} });
    const mockGet = api.get as unknown as vi.Mock;
    mockGet.mockReset();
    mockGet.mockImplementation((url: string, opts: any) => {
      if (url === '/roles' && opts?.params?.tenant_id === tenantContextId) {
        return Promise.resolve({ data: { data: [{ id: tenantRoleId, slug: 'tenant_role' }] } });
      }
      if (url === '/roles' && opts?.params?.scope === 'global') {
        return Promise.resolve({ data: { data: [{ id: globalRoleId, slug: 'super_admin' }] } });
      }
      if (url === '/task-statuses') {
        return Promise.resolve({ data: { data: [] } });
      }
      return Promise.resolve({ data: {} });
    });

    result.selected.value = {
      roles: { view: ['super_admin', 'tenant_role'], edit: ['super_admin'] },
    } as any;

    await result.refreshTenant(tenantContextId, '');
    expect(result.tenantRoles.value.map((r: any) => r.slug)).toContain(
      'super_admin',
    );
    expect(result.selected.value.roles.view).toContain('super_admin');
  });
});

