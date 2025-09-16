/** @vitest-environment jsdom */
import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import Navmenu from '@/components/ui/Sidebar/Navmenu.vue';
import { useAuthStore } from '@/stores/auth';
import { accessForRoute } from '@/constants/menu';

function runVisible(items: any[]) {
  const { visibleItems } = (Navmenu as any).setup(
    { items } as any,
    { attrs: {}, slots: {}, emit: () => {}, expose: () => {} },
  );
  return visibleItems.value as any[];
}

describe('Navmenu feature gating', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('hides menu item when required feature missing', () => {
    const auth = useAuthStore();
    auth.abilities = ['teams.view', 'teams.manage'];
    auth.features = [];
    const items = [
      {
        title: 'Teams',
        link: 'teams.list',
        requiredAbilities: ['teams.view', 'teams.manage'],
        requiredFeatures: ['teams'],
      },
    ];
    expect(runVisible(items)).toHaveLength(0);

    auth.features = ['teams'];
    expect(runVisible(items)).toHaveLength(1);
  });

  it('hides child items when required feature missing', () => {
    const auth = useAuthStore();
    auth.abilities = ['branding.manage'];
    auth.features = [];
    const items = [
      {
        title: 'Settings',
        child: [
          {
            childtitle: 'Branding',
            childlink: 'settings.branding',
            requiredAbilities: ['branding.manage'],
            requiredFeatures: ['branding'],
          },
        ],
      },
    ];
    expect(runVisible(items)).toHaveLength(0);

    auth.features = ['branding'];
    const visible = runVisible(items);
    expect(visible).toHaveLength(1);
    expect(visible[0].child).toHaveLength(1);
  });

  it('shows the profile menu item without feature or ability requirements', () => {
    const auth = useAuthStore();
    auth.abilities = [];
    auth.features = [];

    const items = [
      {
        title: 'Settings',
        child: [
          {
            childtitle: 'Profile',
            childlink: 'settings.profile',
            ...accessForRoute('settings.profile'),
          },
        ],
      },
    ];

    const visible = runVisible(items);
    expect(visible).toHaveLength(1);
    expect(visible[0].child).toHaveLength(1);
    expect(visible[0].child?.[0].childlink).toBe('settings.profile');
  });
});
