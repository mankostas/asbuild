/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp, nextTick } from 'vue';
import { createI18n } from 'vue-i18n';
import AppShell from '@/components/layout/AppShell.vue';
import en from '@/i18n/en.json';
import { createPinia } from 'pinia';

vi.mock('@/stores/branding', () => ({
  useBrandingStore: () => ({ branding: {}, load: vi.fn() }),
}));
vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({ user: null, roles: [] }),
}));
import { ref } from 'vue';
vi.mock('@/stores/drafts', () => ({ useDraftsStore: () => ({ queue: ref([]) }) }));
vi.mock('@/components/ui/Toast.vue', () => ({
  default: { name: 'Toast', template: '<div />' },
}));
vi.mock('@/components/appointments/UploadQueue.vue', () => ({
  default: { name: 'UploadQueue', template: '<div />' },
}));

const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } });

describe('AppShell', () => {
  it('renders and toggles', async () => {
    const app = createApp(AppShell);
    app.use(createPinia());
    app.use(i18n);
    app.component('router-link', { template: '<a><slot /></a>' });
    app.component('router-view', { template: '<div />' });
    const div = document.createElement('div');
    document.body.appendChild(div);
    app.mount(div);
    const skip = div.querySelector('a[href="#main"]') as HTMLAnchorElement;
    expect(skip).not.toBeNull();
    const buttons = div.querySelectorAll('button');
    (buttons[1] as HTMLButtonElement).click();
    (buttons[2] as HTMLButtonElement).click();
      window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', metaKey: true }));
      await nextTick();
      const palette = div.querySelector('[role="dialog"]');
      expect(palette).not.toBeNull();
    });
  });
