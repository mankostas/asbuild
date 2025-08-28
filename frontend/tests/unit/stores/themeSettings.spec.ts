import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

describe('themeSettings store', () => {
  beforeEach(() => {
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
});
