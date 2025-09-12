/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { ref } from 'vue';

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

vi.mock('@/utils/ability', () => ({
  default: () => true,
}));

vi.mock('@/services/api', () => ({
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn(),
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

import TypeForm from '@/views/types/TypeForm.vue';

describe('TypeForm photo serialization', () => {
  it('includes empty photos array when none are defined', () => {
    const result = (TypeForm as any).setup({}, { expose: () => {}, emit: () => {} });
    const sections = result.sections as any;
    const previewSchema = result.previewSchema as any;

    sections.value = [
      {
        id: 1,
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

