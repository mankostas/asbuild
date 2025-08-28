import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('@/services/api', () => ({
  default: { get: vi.fn(), put: vi.fn() },
  registerAuthStore: vi.fn(),
}));

describe('themeSettings store', () => {
  beforeEach(async () => {
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
    vi.resetModules();
    setActivePinia(createPinia());
    const api = (await import('@/services/api')).default;
    api.get.mockReset();
    api.put.mockReset();
  });

  it('migrates and persists defaults to localStorage', async () => {
    const { useThemeSettingsStore } = await import('@/stores/themeSettings');
    // Simulate legacy key and ensure it gets migrated
    localStorage.setItem('themeSettings', JSON.stringify({ sidebarCollaspe: true }));
    const store = useThemeSettingsStore();

    expect(store.sidebarCollasp).toBe(true);

    // Should persist the new key and remove the legacy one
    const saved = JSON.parse(localStorage.getItem('themeSettings') || '{}');
    expect(saved.sidebarCollasp).toBe(true);
    expect(saved.sidebarCollaspe).toBeUndefined();
  });

  it('skips remote calls without themes abilities', async () => {
    const { useAuthStore } = await import('@/stores/auth');
    const auth = useAuthStore();
    auth.accessToken = 'token';
    auth.abilities = [];
    const api = (await import('@/services/api')).default;
    const { useThemeSettingsStore } = await import('@/stores/themeSettings');
    const store = useThemeSettingsStore();

    await store.load();
    expect(api.get).not.toHaveBeenCalled();

    await store.persistRemote();
    expect(api.put).not.toHaveBeenCalled();
  });
});
